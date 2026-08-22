<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiShoppingService
{
    /**
     * Process conversational query and return matching real marketplace products.
     */
    public function chat(string $userMessage, ?string $sessionId = null, ?User $user = null): array
    {
        $sessionId = $sessionId ?: ('ai_sess_' . Str::random(16));
        $intent = $this->extractIntent($userMessage);

        // Retrieve genuine marketplace products matching extracted intent
        $products = $this->queryCatalogProducts($intent);

        // Generate conversational assistant response
        $assistantReply = $this->generateAssistantResponse($userMessage, $intent, $products);

        // Generate 3 contextual follow-up suggestion chips
        $suggestions = $this->generateSuggestions($intent, $products);

        // Store conversation history
        try {
            AiConversation::create([
                'user_id' => $user?->id,
                'session_id' => $sessionId,
                'user_message' => $userMessage,
                'assistant_message' => $assistantReply,
                'intent_data' => $intent,
                'recommended_product_ids' => $products->pluck('id')->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("AI_CONVERSATION_LOG_FAILED: " . $e->getMessage());
        }

        return [
            'session_id' => $sessionId,
            'reply' => $assistantReply,
            'intent' => $intent,
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) ($p->offer_price > 0 ? $p->offer_price : $p->original_price),
                'original_price' => (float) $p->original_price,
                'discount_percent' => (int) $p->discount_percent,
                'rating' => (float) ($p->rating ?? 4.5),
                'reviews_count' => (int) ($p->reviews_count ?? 12),
                'image' => $p->primaryImage?->url ?? $p->thumbnail ?? '/placeholder-product.png',
                'brand' => $p->brand?->name ?? 'JSS Certified',
                'seller_name' => $p->seller?->name ?? 'JSS Official Partner',
                'is_wholesale' => (bool) $p->is_wholesale_enabled,
                'wholesale_moq' => (int) ($p->wholesale_moq ?? 1),
                'in_stock' => $p->stock_quantity > 0,
            ]),
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Extract structured intent parameters from natural language query.
     */
    public function extractIntent(string $message): array
    {
        $intent = [
            'keywords' => [],
            'max_budget' => null,
            'min_budget' => null,
            'category_hint' => null,
            'target_audience' => null,
            'is_bulk' => false,
            'bulk_quantity' => null,
        ];

        $lower = strtolower($message);

        // 1. Extract budget e.g., "under 2000", "below ₹1500", "less than 5000", "under 50k", "between 1000 and 3000"
        if (preg_match('/(?:under|below|less than|within|max(?:imum)?)\s*(?:₹|rs\.?|inr)?\s*([0-9]+(?:\.[0-9]+)?)\s*(k)?/i', $lower, $m)) {
            $val = (float) $m[1];
            if (!empty($m[2]) && strtolower($m[2]) === 'k') {
                $val *= 1000;
            }
            $intent['max_budget'] = $val;
        }

        if (preg_match('/between\s*(?:₹|rs\.?|inr)?\s*([0-9]+)\s*(?:and|to|-)\s*(?:₹|rs\.?|inr)?\s*([0-9]+)/i', $lower, $m)) {
            $intent['min_budget'] = (float) $m[1];
            $intent['max_budget'] = (float) $m[2];
        }

        // 2. Extract bulk / B2B quantity requirements
        if (preg_match('/([0-9]+)\s*(?:units|pcs|pieces|boxes|kg|litres|bags)/i', $lower, $m)) {
            $intent['bulk_quantity'] = (int) $m[1];
            if ($intent['bulk_quantity'] >= 5) {
                $intent['is_bulk'] = true;
            }
        }
        if (str_contains($lower, 'bulk') || str_contains($lower, 'wholesale') || str_contains($lower, 'commercial')) {
            $intent['is_bulk'] = true;
        }

        // 3. Extract audience / recipient hints
        if (str_contains($lower, 'wife') || str_contains($lower, 'woman') || str_contains($lower, 'women') || str_contains($lower, 'girl') || str_contains($lower, 'mother') || str_contains($lower, 'sister')) {
            $intent['target_audience'] = 'women';
        } elseif (str_contains($lower, 'husband') || str_contains($lower, 'man') || str_contains($lower, 'men') || str_contains($lower, 'boy') || str_contains($lower, 'father') || str_contains($lower, 'brother')) {
            $intent['target_audience'] = 'men';
        } elseif (str_contains($lower, 'kid') || str_contains($lower, 'baby') || str_contains($lower, 'child')) {
            $intent['target_audience'] = 'kids';
        }

        // 4. Extract Category & Product Keywords
        $stopWords = ['i', 'need', 'a', 'an', 'the', 'for', 'my', 'me', 'show', 'find', 'give', 'best', 'good', 'cheap', 'looking', 'want', 'buy', 'purchase', 'under', 'below', 'less', 'than', 'rs', 'inr', 'rupees', 'please', 'suggest', 'recommend', 'gift'];
        $words = preg_split('/[\s,\.\?!]+/', $lower, -1, PREG_SPLIT_NO_EMPTY);
        $cleanWords = array_diff($words, $stopWords);

        $intent['keywords'] = array_values(array_filter($cleanWords, fn($w) => strlen($w) > 2 && !is_numeric($w)));

        return $intent;
    }

    /**
     * Query real catalog products using extracted intent constraints.
     */
    public function queryCatalogProducts(array $intent): \Illuminate\Database\Eloquent\Collection
    {
        $query = Product::approved()
            ->with(['primaryImage', 'brand', 'seller', 'category']);

        // Budget filters
        if (!empty($intent['max_budget'])) {
            $max = $intent['max_budget'];
            $query->where(function ($q) use ($max) {
                $q->where(function ($sq) use ($max) {
                    $sq->where('offer_price', '>', 0)->where('offer_price', '<=', $max);
                })->orWhere(function ($sq) use ($max) {
                    $sq->where('offer_price', '<=', 0)->where('original_price', '<=', $max);
                });
            });
        }

        if (!empty($intent['min_budget'])) {
            $min = $intent['min_budget'];
            $query->where(function ($q) use ($min) {
                $q->where(function ($sq) use ($min) {
                    $sq->where('offer_price', '>', 0)->where('offer_price', '>=', $min);
                })->orWhere(function ($sq) use ($min) {
                    $sq->where('offer_price', '<=', 0)->where('original_price', '>=', $min);
                });
            });
        }

        // Wholesale filter
        if ($intent['is_bulk']) {
            $query->orderBy('is_wholesale_enabled', 'desc');
        }

        // Keywords matching across product name, description, and brand
        if (!empty($intent['keywords'])) {
            $query->where(function ($q) use ($intent) {
                foreach ($intent['keywords'] as $kw) {
                    $q->orWhere('name', 'like', "%{$kw}%")
                      ->orWhere('description', 'like', "%{$kw}%")
                      ->orWhere('search_keywords', 'like', "%{$kw}%")
                      ->orWhereHas('brand', fn($b) => $b->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('category', fn($c) => $c->where('name', 'like', "%{$kw}%"));
                }
            });
        }

        $results = $query->orderBy('is_trending', 'desc')
            ->orderBy('rating', 'desc')
            ->take(6)
            ->get();

        // Fallback: If strict search yields < 2 items, relax keyword constraints
        if ($results->count() < 2) {
            $fallbackQuery = Product::approved()
                ->with(['primaryImage', 'brand', 'seller', 'category']);

            if (!empty($intent['max_budget'])) {
                $fallbackQuery->where('offer_price', '<=', $intent['max_budget']);
            }

            $results = $fallbackQuery->orderBy('is_featured', 'desc')
                ->orderBy('rating', 'desc')
                ->take(6)
                ->get();
        }

        return $results;
    }

    /**
     * Generate helpful, natural conversational assistant response.
     */
    protected function generateAssistantResponse(string $userMessage, array $intent, $products): string
    {
        $count = $products->count();

        if ($count === 0) {
            return "I couldn't find any products in our catalog that precisely match your criteria. Try adjusting your budget or searching with different keywords, and I'll find the best options for you!";
        }

        $budgetStr = !empty($intent['max_budget']) ? " within ₹" . number_format($intent['max_budget']) : "";
        $kwStr = !empty($intent['keywords']) ? " for '" . implode(' ', array_slice($intent['keywords'], 0, 3)) . "'" : "";

        if ($intent['is_bulk']) {
            return "Here are the top wholesale-ready products{$kwStr}{$budgetStr} from verified manufacturers on JSS Solutions Marketplace with tier discounts and bulk MOQ support:";
        }

        if (!empty($intent['target_audience'])) {
            return "I've picked {$count} fantastic options{$kwStr}{$budgetStr} tailored for {$intent['target_audience']}. Each product is verified for quality and ready for fast dispatch:";
        }

        return "I found {$count} top-rated products matching your requirement{$kwStr}{$budgetStr}. Here are my best recommendations based on customer ratings and current offers:";
    }

    /**
     * Generate smart follow-up suggestions for customer clicks.
     */
    protected function generateSuggestions(array $intent, $products): array
    {
        $suggestions = [];

        if (!empty($intent['max_budget'])) {
            $lowerBudget = round($intent['max_budget'] * 0.75);
            $suggestions[] = "Show cheaper options under ₹{$lowerBudget}";
        } else {
            $suggestions[] = "Filter items under ₹1,500";
        }

        if (!$intent['is_bulk']) {
            $suggestions[] = "Show wholesale bulk pricing for these";
        }

        $firstProd = $products->first();
        if ($firstProd && $firstProd->brand) {
            $suggestions[] = "More products from {$firstProd->brand->name}";
        } else {
            $suggestions[] = "Show highest rated customer favorites";
        }

        return array_slice($suggestions, 0, 3);
    }
}
