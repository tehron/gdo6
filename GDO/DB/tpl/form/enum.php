<?php /** @var $field \GDO\DB\GDT_Enum **/
$sel = ' selected="selected"';
$val = $field->getVar(); ?>
<div class="gdo-container<?= $field->classError(); ?>">
  <?=$field->htmlTooltip()?>
  <?= $field->htmlIcon(); ?>
  <label><?= $field->displayLabel(); ?></label>
  <select
   name="form[<?=$field->name;?>]"
   <?= $field->htmlRequired(); ?>
   <?= $field->htmlDisabled(); ?>>
<?php if ($field->emptyLabel) : ?>
	  <option value="<?= $field->emptyValue; ?>"<?= $field->emptyValue === $val ? $sel : ''; ?>><?= $field->emptyLabel; ?></option>
<?php endif; ?>
	<?php foreach ($field->enumValues as $enumValue) : ?>
	  <option value="<?= $enumValue; ?>"<?= $enumValue === $val ? $sel : ''; ?>><?= $field->enumLabel($enumValue); ?></option>
	<?php endforeach; ?>
  </select>
  <div class="gdo-error"><?= $field->error; ?></div>
</div>
