    <p>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a>
    </p>
    <form method="post" action="<?php echo $this->formaction ?>" enctype="multipart/form-data">
        <fieldset id="contact-fieldset">
            <legend>View Document: <?php echo $this->bean->document_name; ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>

            <p><a href="<?php echo $this->formaction ?>?action=edit&id=<?php echo $this->id ?>" />Edit</a></p>
            <?php foreach ($this->metadata as $field => $def): if ($this->renderField($field)): $fieldVal = $this->bean->$field; ?>
            <p>
                <strong><?php echo $this->getModuleString($def['vname']) ?></strong>
                <?php echo $fieldVal ?>
                <?php if ($field === 'filename'): ?>
                    <?php if (!empty($fieldVal)): ?><span class="small">
                    <a href="<?php echo $this->formaction ?>?action=removedoc&id=<?php echo $this->bean->id ?>&field=filename">Remove attachment</a>
                    </span><?php endif; ?><br /><span class="small">
                    <?php echo empty($fieldVal) ? 'Add an' : 'Update existing'; ?> attachment:
                    <input type="file" name="filename" id="filename" />
                    <input type="hidden" name="id" value="<?php echo $this->bean->id ?>" />
                    <input type="hidden" name="action" value="detail" />
                    Base64 Encoded: <input type="checkbox" name="encode" />
                    <input type="submit" name="submit" value="Save" />
                    </span>
                <?php endif; ?>
            </p>
            <?php endif; endforeach; ?>

            <!--
            <p>
                <input type="hidden" name="id" value="<?php echo $this->document->id ?>" />
                <input type="hidden" name="action" value="detail" />
                <input type="submit" name="submit" value="Save" />
            </p>
            -->
        </fieldset>
    </form>
    <pre><?php var_dump($this->bean->filename) ?></pre>