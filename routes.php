<?php

Route::post('/chunk-upload', 'Pensoft\InternalDocuments\Components\InternalRepository@chunkUpload')->middleware('web');
