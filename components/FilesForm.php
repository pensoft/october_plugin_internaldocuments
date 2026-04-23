<?php

namespace Pensoft\Internaldocuments\Components;

use \Cms\Classes\ComponentBase;
use Pensoft\InternalDocuments\Models\Subfolders;
use Validator;
use Redirect;
use System\Models\File;
use System\Classes\MediaLibrary;
use Auth;
use Illuminate\Contracts\Filesystem\Filesystem;

class FilesForm extends ComponentBase
{

	public function componentDetails()
	{
		return [
			'name' => 'Files Form',
			'description' => 'Simple files and folders form.'
		];
	}

	private function updateFileUserId($recordFiles, $model, $type){
		$user = Auth::getUser();
		foreach ($recordFiles as $fileData) {
			$file = new File();
			$file->data = $fileData;
			$file->is_public = true;
			$file->user_id = $user->id;
			$file->save();

			switch ($type){
				case 'files':
					$model->files()->add($file);
				default:
					break;
				case 'images':
					$model->images()->add($file);
					break;
			}
		}
	}

	public function onSubmit(){
		$validator = Validator::make(
			[
				'name' => request()->input('name'),
				'files' => request()->file('files'),
				'images' => request()->file('images'),
			],
			[
				'name' => 'required_without_all:files,images',
				'files' => 'required_without_all:images,name',
				'images' => 'required_without_all:name,files',
			]
		);

		if($validator->fails()){
			return Redirect::back()->withErrors($validator);
		}

		if(request()->input('name')){
			$subfolder = new Subfolders();
			$subfolder->name = request()->input('name');
			$subfolder->parent_id = request()->input('parent');
		}else{
			$subfolder = Subfolders::find(request()->input('parent'));

		}

		//update user_id in system_files
		if(request()->file('files'))
			$this->updateFileUserId(request()->file('files'), $subfolder, 'files');
		if(request()->file('images'))
			$this->updateFileUserId(request()->file('images'), $subfolder, 'images');

		$subfolder->user_id = request()->input('user_id');
		$subfolder->save();

		$folderData = Subfolders::where('id', request()->input('parent'))->first();

		$this->page['folder'] = $folderData;
		$this->page['group_id'] = request()->input('parent');
		$this->page['user'] = Auth::getUser();

	}

	public function onFolderRename(){
		$folderId = request()->input('id');
		$folderName = request()->input('name');

		$folder = Subfolders::find($folderId);
		if($folder){
			Subfolders::where('id', $folderId)->update(['name' => $folderName]);
		}

		$this->page['name'] = $folderName;
	}

	public function onImageUpload(){
		$image = request()->all();
		$images = $image['images'];

		$output = '';
		foreach ($images as $photo) {
			$file = (new File())->fromPost($photo);
			$output .= '<img src="' . $file->getThumb(170, 120, ['mode' => 'crop']) . '"> ';
		}

		return  [
			'#image_result' => $output
		];
	}

	public function onFileUpload(){
		$image = request()->all();
		$images = $image['files'];

		$output = '';
		foreach ($images as $photo) {
			$file = (new File())->fromPost($photo);
			if($file->getExtension() == 'docx' || $file->getExtension() == 'doc'){
				$mediaFileName = 'files_doc.svg';
			}else if($file->getExtension() == 'pdf'){
				$mediaFileName = 'files_pdf.svg';
			}else{
				$mediaFileName = 'files_file.svg';
			}
			$output .= '<img src="' . MediaLibrary::url($mediaFileName) . '" style="width: 30px; float: left; margin-right: 8px;"> '. $file->getFilename().' <br>';
		}

		return  [
			'#file_result' => $output
		];
	}

	public function onSortFiles(){
		parse_str(request()->post('sortOrder'), $output);
		$reorderIds = $output['sortItem'];
		$subfolderId = request()->post('subfolderId');
		$moved = [];
		$position = 0;

		if (is_array($reorderIds) && count($reorderIds)) {
			foreach ($reorderIds as $id) {
				if (in_array($id, $moved) || !$record = File::find($id))
					continue;
				$record->sort_order = $position;
				$record->save();
				$moved[] = $id;
				$position++;
			}
		}

		$subfolderData = Subfolders::where('parent_id', $subfolderId)->get();

		return [
			'#sortable' => $this->renderPartial('components/documents-files', ['files' => $subfolderData, 'group_id' => $subfolderId])
		];
	}

	public function onSortFolders(){
		parse_str(request()->post('sortOrder'), $output);

		$sourceNode = Subfolders::find(request()->post('sourceNode'));
		$targetNode = request()->post('targetNode') ? Subfolders::find(request()->post('targetNode')) : null;

		if ($sourceNode == $targetNode) {
			return;
		}

		switch (request()->post('position')) {
			case 'left':
			default:
				$sourceNode->moveBefore($targetNode);
				break;

			case 'right':
				$sourceNode->moveAfter($targetNode);
				break;
		}

	}
}