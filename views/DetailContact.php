    <p>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a>
    </p>
    <form method="post" action="<?php echo $this->formaction ?>" enctype="multipart/form-data">
        <fieldset id="contact-fieldset">
            <legend>Contact record for <?php echo $this->contact->full_name ?></legend>
            <?php if ($this->error): ?>
            <p class="error">
                <?php echo $this->error ?>
            </p>
            <?php endif ?>
            <p>
                <?php echo empty($this->contact->picture) ? 'Add ' : 'Update '; echo $this->contact->full_name; ?>'s profile picture:
                <input type="file" name="picture" id="picture" />
            </p>
            <p>
                <input type="hidden" name="id" value="<?php echo $this->contact->id ?>" />
                <input type="hidden" name="action" value="detail" />
                <input type="submit" name="submit" value="Save" />
            </p>
            <?php if (!empty($this->contact->picture)): ?>
            <p>
                <a href="<?php echo $this->formaction ?>?action=removedoc&id=<?php echo $this->contact->id ?>&field=picture">Remove existing image</a><br />
                <img src="<?php echo $this->formaction ?>?action=download&id=<?php echo $this->contact->id ?>&field=picture&ck=<?php echo time() ?>" />
            </p>
            <?php endif; ?>
        </fieldset>
    </form>