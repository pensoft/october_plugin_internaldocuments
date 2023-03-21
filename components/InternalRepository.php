<?php namespace Pensoft\InternalDocuments\Components;

use Cms\Classes\ComponentBase;
use Pensoft\Internaldocuments\Models\Subfolders;
use System\Models\File;
use RainLab\User\Models\UserGroup;
use Input;
use Validator;
use Redirect;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InternalRepository extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'InternalRepository Component',
            'description' => 'Internal Repository files, folders and subfolders list'
        ];
    }

    public function defineProperties()
    {
        return [
            'useAWSFiles' => [
                'title' => 'Use AWS S3 files',
                'type' => 'checkbox',
                'default' => false
            ],
        ];
    }

	public function onRun()
	{
		$this->addJs('assets/js/popper.min.js');
		$this->addJs('assets/js/tippy-bundle.umd.min.js');
		$this->addJs('assets/js/def.js');

		if(get('download')){
			$this->downloadFiles();
		}

		$this->page['queries'] = http_build_query(Input::all());
		$this->page['is_download'] = get('download', false);

		if($query = post('query')){
			$subFolders = Subfolders::where('name', 'iLIKE', '%' . $query . '%')->get();
			$this->page['has_query'] = true;
			$this->page['sub_folders'] = $subFolders;
			$this->page['files'] = File::where(
				'attachment_type', Subfolders::class)
				->where('file_name', 'ilike', '%'.$query.'%')
				->get()
				->map(function ($file){
					$folder = Subfolders::find($file->attachment_id);
					$file->folderData = $folder;
					return $file;
				});
		}

		$this->page['slug'] = $this->param('slug');
		if($this->param('slug')){
			$this->page['folder'] = Subfolders::select('pensoft_internaldocuments_subfolders.*')
				->where('slug', ''.$this->param('slug').'')
				->first();
		}else{
			$this->page['folders'] = Subfolders::where('pensoft_internaldocuments_subfolders.parent_id', null)->get();
		}

		if($this->param('parent_id')){
			$this->page['expand'] = $this->param('parent_id');
		}

		$this->page['function'] = new class {
			const AVAILABLE_MIME_TYPE_ICONS = [
				'application/pdf' => 'files_pdf.svg',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'files_doc.svg',
				'application/msword' => 'files_doc.svg',
			];

			public function get($id) {
				$folder = Subfolders::select('pensoft_internaldocuments_subfolders.*')
				->where('id', $id)
				->first();

				$folder_images = $folder->files()->get()
					->filter(function($file){return $this->filterImages($file);});
				$folder_files = $folder->files()->get()
					->filter(function($file){return $this->filterFiles($file);})
					->map(function($file){return $this->prependFiles($file);});

				return [
					"images" => $folder_images,
					"files" => $folder_files,
				];
			}
			private function filterImages($file){
				$mimeType = $file->content_type;
				switch($mimeType){
					case 'image/jpeg':
					case 'image/png':
					case 'image/svg+xml':
						return true;
					default:
						return false;
				}
			}
			private function filterFiles($file){

				return !$this->filterImages($file);
			}
			private function prependFiles($file)
			{
				$filename = 'files_file.svg';

				if(array_key_exists($file->content_type, self::AVAILABLE_MIME_TYPE_ICONS)){
					$filename = self::AVAILABLE_MIME_TYPE_ICONS[$file->content_type];
				}

				$file->src = Storage::url('media/'.$filename);
				return $file;
			}
		};
	}

	public function chunkUpload(Request $request)
	{
		$receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

		if ($receiver->isUploaded() === false) {
			throw new UploadMissingFileException();
		}

		$save = $receiver->receive();

		if ($save->isFinished()) {
			return $this->saveFile($save->getFile());
		}

		// we are in chunk mode, lets send the current progress
		/** @var AbstractHandler $handler */
		$handler = $save->handler();

		return response()->json([
			"done" => $handler->getPercentageDone(),
		]);
	}

	private function saveFile(UploadedFile $file)
	{
		$extension = $file->getClientOriginalExtension();
		$mimeType = $file->getMimeType();
		$fileSize = $file->getClientSize();
		$fileName = $file->getClientOriginalName();
		$diskName = str_replace('.', '', uniqid(null, true)).'.'.$extension;

		$fileModel = new File();
		$fileModel->disk_name = $diskName;
		$fileModel->file_name = $fileName;
		$fileModel->content_type = $mimeType;
		$fileModel->file_size = $fileSize;
		$fileModel->is_public = true;

		if(Auth::check()){
			$user = Auth::getUser();
			$fileModel->user_id = $user->id;
		}
		$filePath = 'storage/app/'.dirname($fileModel->getDiskPath());
		$fileModel->save();

		Storage::makeDirectory($filePath, 0777, true);
		$file->move($filePath, $diskName);

		$subfolders = new Subfolders();
		$subfolder = null;
		if($parent_id = request('parent_id', false)){
			$subfolder = $subfolders->find($parent_id);
		}
		if($subfolder){
			$subfolder->files()->add($fileModel);
		}

		return response()->json([
			'path' => $filePath,
			'name' => $fileName,
			'mime_type' => $mimeType
		]);
	}

	public function downloadFiles()
	{
		$download = Input::get('download');
		$file_ids = explode(',', $download);
		if(count($file_ids) === 1){
			$file = File::find($file_ids[0]);
			$filePath = 'storage/app/'.dirname($file->getDiskPath());
			$this->response_stream($filePath.'/'.$file->disk_name, $file->file_name);
            exit();
		}else if(count($file_ids) > 1){
			$files = File::find($file_ids);
			$zip_file = tempnam(sys_get_temp_dir(), "archives");
			$zip = new \ZipArchive();
			$zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
			foreach ($files as $item) {
				$fileId = $item['id'];
				$file = File::find($fileId);
				$filePath = $file->getLocalPath();
				$filename = $item['file_name'];
				$i = 1;
				if ($filename == basename($filePath)) {
					$filename = $i . '-' . basename($filePath);
					$i++;
				} else {
					$filename = basename($filePath);
					$i = 1;
				}
				$zip->addFile(
					$filePath,
					$filename
				);
			}
			$zip->close();
			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=archives.zip");
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile($zip_file);
			exit();
		}
	}

	private function response_stream($filePath, $fileName)
	{
		$response = new StreamedResponse(
			function() use ($filePath, $fileName) {
				// Open output stream
				if ($file = fopen($filePath, 'rb')) {
					while(!feof($file) and (connection_status()==0)) {
						print(fread($file, 4096));
						flush();
					}
					fclose($file);
				}
			},
			200,
			[
				'Content-Type' => 'application/octet-stream',
				'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
			]);

		return $response->send();
	}

	public function onDeleteFile()
	{
		$fileId = post('id');
		$file = File::find($fileId);
		if($file)
			$file->delete();
		return ['#delete_result_'.$fileId => ''];
	}

	public function onDeleteFolder()
	{
		$folderId = post('id');
		$folder = Subfolders::find($folderId);
		if($folder){
			$folder->delete();
		}
		return ['#delete_result_'.$folderId => ''];
	}

	public function onSubmit(){
		$validator = Validator::make(
			[
				'name' => Input::get('name'),
				'files' => Input::file('files'),
				'images' => Input::file('images'),
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

		if(Input::get('name')){
			$subfolder = new Subfolders();
			$subfolder->name = Input::get('name');
			$subfolder->parent_id = Input::get('parent');
		}else{
			$subfolder = Subfolders::find(Input::get('parent'));

		}

		$subfolder->user_id = Input::get('user_id');
		$subfolder->save();

		$folderData = Subfolders::where('id', Input::get('parent'))->first();

		$this->page['folder'] = $folderData;
		$this->page['group_id'] = Input::get('parent');
		$this->page['user'] = Auth::getUser();

		$user = Auth::getUser();
		$this->page['is_guest'] = $user->inGroup(UserGroup::where('code', 'guest')->first());
		$this->page['is_registered'] = $user->inGroup(UserGroup::where('code', 'registered')->first());
		$this->page['is_editor'] = $user->inGroup(UserGroup::where('code', 'internal-users')->first());

	}

	public function onFolderRename(){
		$folderId = Input::get('id');
		$folderName = Input::get('name');

		$folder = Subfolders::find($folderId);
		if($folder){
			Subfolders::where('id', $folderId)->update(['name' => $folderName]);
		}

		$this->page['name'] = $folderName;
	}

	public function onSortFiles(){
		parse_str(post('sortOrder'), $output);
		$reorderIds = $output['sortItem'];
		$subfolderId = post('subfolderId');
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

		$this->page['files'] = $subfolderData;
		$this->page['group_id'] = $subfolderId;
	}

	public function onSortFolders(){
		parse_str(post('sortOrder'), $output);

		$sourceNode = Subfolders::find(post('sourceNode'));
		$targetNode = post('targetNode') ? Subfolders::find(post('targetNode')) : null;

		if ($sourceNode == $targetNode) {
			return;
		}

		switch (post('position')) {
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
