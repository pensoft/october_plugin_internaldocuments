<?php namespace Pensoft\InternalDocuments\Models;

use Model;
use BackendAuth;

/**
 * Model
 */
class Subfolders extends Model
{
    use \October\Rain\Database\Traits\Validation;
	use \October\Rain\Database\Traits\NestedTree;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'pensoft_internaldocuments_subfolders';

    /**
     * @var array Validation rules
     */
    public $rules = [
    ];

	protected $jsonable = ['files_groups'];

	protected $nullable = ['parent_id'];

    public $belongsTo = [
		'parent' => 'Pensoft\Internaldocuments\Models\Subfolders',
		'user' => 'Rainlab\User\Models\User'
	];

	public $attachMany = [
		'files' => ['System\Models\File', 'order' => 'sort_order'],
		'images' => ['System\Models\File', 'order' => 'sort_order'],
	];

	public $attachOne = [
		'cover' => 'System\Models\File'
	];

    // Add  for revisions limit
    public $revisionableLimit = 200;

    // Add for revisions on particular field
    protected $revisionable = ["id", "name"];

    // Add  below relationship with Revision model
    public $morphMany = [
        'revision_history' => ['System\Models\Revision', 'name' => 'revisionable']
    ];


    // Add below function use for get current user details
    public function diff()
    {
        $history = $this->revision_history;
    }

    public function getRevisionableUser()
    {
        return BackendAuth::getUser()->id;
    }
}
