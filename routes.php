<?php

use Pensoft\InternalDocuments\Components\InternalRepository;

Route::post('/chunk-upload', [InternalRepository::class, 'chunkUpload'])->middleware('web');