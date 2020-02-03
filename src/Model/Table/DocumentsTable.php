<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\Filesystem\File;

/**
 * Documents Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \App\Model\Entity\Document get($primaryKey, $options = [])
 * @method \App\Model\Entity\Document newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Document[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Document|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Document saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Document patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Document[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Document findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class DocumentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->setTable('documents');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('type')
            ->maxLength('type', 255)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('description')
            ->maxLength('description', 255)
            ->allowEmptyString('description');

        $validator
            ->scalar('path')
            ->maxLength('path', 255)
            ->requirePresence('path', 'create')
            ->notEmptyString('path');

        $validator
            ->integer('size')
            ->requirePresence('size', 'create')
            ->notEmptyString('size');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * beforeMarshall triggered before the validation process
     * this will be fired by the call to patchEntity() in the controller
     * calculates model fields size, type, and optionally name from uploaded file data
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if(isset($data['file']))
        {
            if(empty($data['name'])) 
            {
                $data['name'] = $data['file']['name'];
            }
            $data['type'] = $data['file']['type'];
            $data['size'] = $data['file']['size'];
            $data['path'] = $data['file']['tmp_name'];
        }
    }

    /**
     * beforeSave persists the temporary file to the file system at the destination
     * before persisting file metadata to Documents table
     *
     * @return null|false
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $dest = new File(WWW_ROOT . 'files' . DS . $entity['name']);

        // ensure the filename is safe
        $safe_name = $dest->safe() . ($dest->ext() ?: $dest->ext());
        if ($safe_name != $dest->name)
        {
            $dest->name = $safe_name;
            $dest->path = $dest->folder()->path . DS . $dest->name;
        }

        //check mime type
        if(!$dest->ext()) 
        {
            $mimes = new \Mimey\MimeTypes;
            $dest->name = $dest->name() . '.' . $mimes->getExtension($entity['type']);
            $dest->path = $dest->folder()->path . DS . $dest->name;
        }

        // ensure file does not exist
        if($dest->exists())
        {
            return false; 
        } 

        // save the temp file to filesystem at destination
        if(move_uploaded_file($entity['path'], $dest->path)) 
        {
            $entity['path'] = $dest->name;
        } else 
        {
            return false;
        }
        //$event->stopPropagation();
    }

    /**
     * beforeDelete remove the file from its location in the file system
     * before removing file metadata from documents table
     *
     * @return null|false
     */
    public function beforeDelete(Event $event, Entity $entity, ArrayObject $options) 
    {
        $file = new File(WWW_ROOT . 'files' . DS . $entity['path']);
        if(!$file->delete())
        {
            return false;
        }
    }
}
