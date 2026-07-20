<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload a new media asset.
     */
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $fileName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $file->getClientOriginalExtension();
        
        $path = $file->storeAs('uploads', $fileName, 'public');

        $media = Media::create([
            'model_type' => $request->input('model_type'),
            'model_id' => $request->input('model_id'),
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'disk' => 'public',
            'collection' => $request->input('collection', 'default'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data' => new MediaResource($media),
        ], 201);
    }
}
