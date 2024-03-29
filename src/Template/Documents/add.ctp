<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Document $document
 */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('List Documents'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="documents form large-9 medium-8 columns content">
    <?= $this->Form->create($document, ['type' => 'file']) ?>
    <fieldset>
        <legend><?= __('Add Document') ?></legend>
        <?php
            echo $this->Form->control('user_id', ['type' => 'hidden', 'value' => $user_id]);
            echo $this->Form->control('name', [
                'required' => False, 
                'label' => 'Name, if blank uploaded filename will be used'
            ]);
            echo $this->Form->control('file', [
                'required' => true,
                'type' => 'file', 
                'options' => [
                    'accept' => '.docx, .doc, .xml, .pdf, .xls, .xlsx'
                ]
            ]);
            echo $this->Form->control('description', ['label' => 'Description, optional']);
            //echo $this->Form->control('type');
            //echo $this->Form->control('path');
            //echo $this->Form->control('size');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
