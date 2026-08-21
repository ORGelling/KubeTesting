<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Services\RabbitPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $files = MediaFile::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($files);
    }

    public function store(
        Request $request,
        RabbitPublisher $rabbit,
    ): JsonResponse {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200',
            ],
        ]);

        $uploadedFile = $request->file('file');

        $storagePath = $uploadedFile->store(
            "uploads/{$request->user()->id}",
            's3',
        );

        $mediaFile = MediaFile::create([
            'user_id' => $request->user()->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $storagePath,
            'mime_type' => $uploadedFile->getMimeType(),
            'size_bytes' => $uploadedFile->getSize(),
            'status' => 'pending',
        ]);

        $rabbit->publishFileUploaded($mediaFile->id);

        return response()->json($mediaFile, 201);
    }

    public function download(Request $request, MediaFile $mediaFile)
    {
        abort_unless(
            $mediaFile->user_id === $request->user()->id,
            403,
        );

        return Storage::disk('s3')->download(
            $mediaFile->storage_path,
            $mediaFile->original_name,
        );
    }
}
