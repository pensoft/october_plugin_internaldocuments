<?php namespace Pensoft\InternalDocuments\Models;

use Model;
use BackendAuth;
use System\Models\File;
use System\Models\Revision;
use RainLab\User\Models\User;

/**
 * Model
 */
class Subfolders extends Model
{
    use \October\Rain\Database\Traits\Validation;
	use \October\Rain\Database\Traits\NestedTree;

    use \October\Rain\Database\Traits\SoftDelete;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

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
		'parent' => [Subfolders::class],
		'user' => [User::class]
	];

	public $attachMany = [
		'files' => [File::class, 'order' => 'sort_order', 'delete' => false],
		'images' => [File::class, 'order' => 'sort_order', 'delete' => false],
	];

	public $attachOne = [
		'cover' => [File::class, 'delete' => false],
	];

    // Add  for revisions limit
    public $revisionableLimit = 200;

    // Add for revisions on particular field
    protected $revisionable = ["id", "name"];

    // Add  below relationship with Revision model
    public $morphMany = [
        'revision_history' => [Revision::class, 'name' => 'revisionable']
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