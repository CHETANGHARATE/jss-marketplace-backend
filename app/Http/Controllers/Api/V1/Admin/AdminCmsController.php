<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCmsController extends Controller
{
    private function getCmsData(string $key, array $default = []): array
    {
        $setting = Setting::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }
        $val = $setting->value;
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : $default;
        }
        return is_array($val) ? $val : $default;
    }

    private function saveCmsData(string $key, array $data): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $data,
                'group' => 'general',
                'is_public' => true,
            ]
        );
    }

    // ─── Banners ─────────────────────────────────────────────────────────────

    public function indexBanners(): JsonResponse
    {
        $banners = $this->getCmsData('cms_banners', [
            [
                'id' => 1,
                'title' => 'Monsoon Big Grocery & Essentials Sale',
                'location' => 'home_hero',
                'image_url' => '/images/banners/monsoon-hero.jpg',
                'target_url' => '/products?category=grocery',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'title' => 'Pure Desi Ghee & Spices Festival',
                'location' => 'home_strip',
                'image_url' => '/images/banners/spices-banner.jpg',
                'target_url' => '/products?category=spices',
                'sort_order' => 2,
                'is_active' => true,
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location' => 'required|string|in:home_hero,home_strip,category_top,sidebar',
            'image_url' => 'required|string',
            'target_url' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banners = $this->getCmsData('cms_banners', []);
        $newId = count($banners) > 0 ? max(array_column($banners, 'id')) + 1 : 1;

        $newBanner = [
            'id' => $newId,
            'title' => $validated['title'],
            'location' => $validated['location'],
            'image_url' => $validated['image_url'],
            'target_url' => $validated['target_url'] ?? '',
            'sort_order' => $validated['sort_order'] ?? count($banners) + 1,
            'is_active' => $validated['is_active'] ?? true,
            'created_at' => now()->toIso8601String(),
        ];

        $banners[] = $newBanner;
        $this->saveCmsData('cms_banners', $banners);

        return response()->json([
            'success' => true,
            'message' => 'Banner created successfully.',
            'data' => $newBanner,
        ], 201);
    }

    public function updateBanner(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|in:home_hero,home_strip,category_top,sidebar',
            'image_url' => 'sometimes|required|string',
            'target_url' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $banners = $this->getCmsData('cms_banners', []);
        $found = false;
        $updatedItem = null;

        foreach ($banners as &$item) {
            if ($item['id'] === $id) {
                $item = array_merge($item, $validated);
                $updatedItem = $item;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Banner not found.'], 404);
        }

        $this->saveCmsData('cms_banners', $banners);

        return response()->json([
            'success' => true,
            'message' => 'Banner updated successfully.',
            'data' => $updatedItem,
        ]);
    }

    public function toggleBannerStatus(int $id): JsonResponse
    {
        $banners = $this->getCmsData('cms_banners', []);
        $found = false;
        $updatedItem = null;

        foreach ($banners as &$item) {
            if ($item['id'] === $id) {
                $item['is_active'] = !($item['is_active'] ?? true);
                $updatedItem = $item;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Banner not found.'], 404);
        }

        $this->saveCmsData('cms_banners', $banners);

        return response()->json([
            'success' => true,
            'message' => 'Banner status updated.',
            'data' => $updatedItem,
        ]);
    }

    public function destroyBanner(int $id): JsonResponse
    {
        $banners = $this->getCmsData('cms_banners', []);
        $filtered = array_values(array_filter($banners, fn($b) => $b['id'] !== $id));
        $this->saveCmsData('cms_banners', $filtered);

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully.',
        ]);
    }

    // ─── Popups ──────────────────────────────────────────────────────────────

    public function getPopups(): JsonResponse
    {
        $popups = $this->getCmsData('cms_popups', [
            [
                'id' => 1,
                'title' => 'Download JSS Mobile App',
                'content' => 'Get extra ₹100 cashback on your first app purchase. Scan QR code or install now.',
                'image_url' => '',
                'cta_text' => 'Get App',
                'cta_url' => '/app',
                'is_active' => false,
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $popups,
        ]);
    }

    public function updatePopup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image_url' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $popups = [[
            'id' => 1,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'image_url' => $validated['image_url'] ?? '',
            'cta_text' => $validated['cta_text'] ?? 'Learn More',
            'cta_url' => $validated['cta_url'] ?? '',
            'is_active' => $validated['is_active'] ?? false,
            'updated_at' => now()->toIso8601String(),
        ]];

        $this->saveCmsData('cms_popups', $popups);

        return response()->json([
            'success' => true,
            'message' => 'Popup announcement updated successfully.',
            'data' => $popups[0],
        ]);
    }

    // ─── Static Pages ────────────────────────────────────────────────────────

    public function getPages(): JsonResponse
    {
        $pages = $this->getCmsData('cms_pages', [
            [
                'id' => 1,
                'slug' => 'about-us',
                'title' => 'About JSS Solutions Marketplace',
                'content' => 'JSS Solutions is a premier multi-vendor marketplace connecting authentic merchants and buyers across India.',
                'is_published' => true,
                'updated_at' => now()->toIso8601String(),
            ],
            [
                'id' => 2,
                'slug' => 'privacy-policy',
                'title' => 'Privacy Policy & Data Security',
                'content' => 'We value your privacy and comply with standard data protection practices.',
                'is_published' => true,
                'updated_at' => now()->toIso8601String(),
            ],
            [
                'id' => 3,
                'slug' => 'terms-conditions',
                'title' => 'Terms & Conditions',
                'content' => 'Read our terms of service governing purchases, deliveries, returns, and vendor transactions.',
                'is_published' => true,
                'updated_at' => now()->toIso8601String(),
            ],
            [
                'id' => 4,
                'slug' => 'return-policy',
                'title' => 'Return & Refund Policy',
                'content' => 'Easy 7-day returns for eligible unopened items with fast wallet or bank refunds.',
                'is_published' => true,
                'updated_at' => now()->toIso8601String(),
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $pages,
        ]);
    }

    public function updatePage(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
        ]);

        $pages = $this->getCmsData('cms_pages', []);
        $found = false;
        $updatedPage = null;

        foreach ($pages as &$page) {
            if ($page['id'] === $id) {
                $page['title'] = $validated['title'];
                $page['content'] = $validated['content'];
                $page['is_published'] = $validated['is_published'] ?? true;
                $page['updated_at'] = now()->toIso8601String();
                $updatedPage = $page;
                $found = true;
                break;
            }
        }

        if (!$found) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        $this->saveCmsData('cms_pages', $pages);

        return response()->json([
            'success' => true,
            'message' => "Page '{$updatedPage['title']}' saved successfully.",
            'data' => $updatedPage,
        ]);
    }

    // ─── FAQ Center ──────────────────────────────────────────────────────────

    public function getFaqs(): JsonResponse
    {
        $faqs = $this->getCmsData('cms_faqs', [
            [
                'id' => 1,
                'question' => 'How do I track my order delivery?',
                'answer' => 'You can track your package under My Orders section using the live courier AWB tracking number.',
                'category' => 'Orders & Shipping',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'question' => 'What payment methods are supported?',
                'answer' => 'We support Razorpay (UPI, Credit/Debit Cards, Net Banking, Wallets) and Cash on Delivery (COD).',
                'category' => 'Payments',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'question' => 'How can I become a seller on JSS Marketplace?',
                'answer' => 'Click "Sell with Us" in the header, complete your seller profile, and submit PAN/GST documents for quick approval.',
                'category' => 'Vendor Selling',
                'is_active' => true,
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $faqs,
        ]);
    }

    public function storeFaq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'category' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $faqs = $this->getCmsData('cms_faqs', []);
        $newId = count($faqs) > 0 ? max(array_column($faqs, 'id')) + 1 : 1;

        $newFaq = [
            'id' => $newId,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'category' => $validated['category'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        $faqs[] = $newFaq;
        $this->saveCmsData('cms_faqs', $faqs);

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully.',
            'data' => $newFaq,
        ], 201);
    }

    public function destroyFaq(int $id): JsonResponse
    {
        $faqs = $this->getCmsData('cms_faqs', []);
        $filtered = array_values(array_filter($faqs, fn($f) => $f['id'] !== $id));
        $this->saveCmsData('cms_faqs', $filtered);

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully.',
        ]);
    }
}
