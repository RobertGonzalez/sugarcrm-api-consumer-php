    <p>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a>
    </p>
    <form method="post" action="<?php echo $this->formaction ?>" enctype="multipart/form-data">
        <fieldset id="contact-fieldset">
            <legend><?php echo $this->note->name, ' - ', $this->note->description; ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <p>
                <?php echo empty($this->note->filename) ? 'Add' : 'Update'; ?> attached document:
                <span class="small">
                <input type="file" name="filename" id="filename" />
                <?php if (!empty($this->note->filename)): ?>
                    <a href="<?php echo $this->formaction ?>?action=removedoc&id=<?php echo $this->note->id ?>&field=filename">Remove attachment <?php echo $this->note->filename ?></a> |
                    <a href="?action=download&id=<?php echo $this->note->id ?>&field=filename">Download <?php echo $this->note->filename ?></a>
                <?php endif; ?>
                <input type="hidden" name="id" value="<?php echo $this->note->id ?>" />
                <input type="hidden" name="action" value="detail" />
                Base64 Encoded: <input type="checkbox" name="encode" />
                <input type="submit" name="submit" value="Save" />
                </span>
            </p>
        </fieldset>
    </form>