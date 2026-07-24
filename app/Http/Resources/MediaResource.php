<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'url' => str_starts_with($this->file_path, 'http') 
                ? $this->file_path 
                : Storage::disk($this->disk)->url($this->file_path),
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'collection' => $this->collection,
            'sort_order' => $this->sort_order,
        ];
    }
}
