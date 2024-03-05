    <p>
        <a href="<?php echo $this->formaction ?>">&laquo; Back to list</a>
    </p>

    <table id="list-table">
        <tr>
            <!--<th>All Modules</th>
            <th>Fields for <?php echo $this->module ?></th>
            <th>Fields for <?php echo $this->module ?> (<?php echo count($this->list['fields']) ?>)</th>-->
            <th>Relationships Fields for <?php echo $this->module ?> (<?php echo count($this->list['relationships']) ?>)</th>
            <th>Relationships for <?php echo $this->module ?> (<?php echo count($this->list['shared']) ?>)</th>
            <th>Relationship Modules for <?php echo $this->module ?> (<?php echo count($this->list['relmodules']) ?>)</th>
        </tr>

        <tr>
            <!--<td>
                <ul>
                    <?php foreach ($this->list['metadata']['moduleList'] as $mod): ?>
                    <li><?php echo $mod ?></li>
                    <?php endforeach ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ($this->list['metadata']['modules'][$this->module]['fields'] as $name => $defs): ?>
                    <li><?php echo $name, ': ', $defs['name']; ?></li>
                    <?php endforeach ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ($this->list['fields'] as $name => $defs): ?>
                    <li><?php echo $name; if (!empty($defs['rel'])) echo ' RELATED'; ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>-->
            <td>
                <ul>
                    <?php foreach ($this->list['relationships'] as $name => $rel): ?>
                    <li><?php echo "$name<br />&nbsp;&nbsp;Name: $rel[name]<br />&nbsp;&nbsp;Module: $rel[module]"; ?></li>
                    <?php endforeach; ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ($this->list['shared'] as $name => $relname): ?>
                    <li><?php echo "$name<br />&nbsp;&nbsp;Rel Name: $relname"; ?></li>
                    <?php endforeach ?>
                </ul>
            </td>
            <td>
                <ul>
                    <?php foreach ($this->list['relmodules'] as $name => $count): ?>
                    <li><?php echo "$name - $count"; ?></li>
                    <?php endforeach ?>
                </ul>
            </td>
        </tr>
    </table>