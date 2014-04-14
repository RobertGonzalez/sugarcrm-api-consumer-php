    <table id="list-table">
        <tr>
        <?php foreach ($this->headings as $label) : ?>
            <th><?php echo $label ?></th>
        <?php endforeach; ?>
        </tr>
        <?php $ix = 0; $on = false; if ($this->rows): foreach ($this->rows as $row): $class = $on ? 'on' : 'off'; ?>
        <tr id="row-<?php echo $ix ?>" class="row <?php echo $class ?>">
            <?php foreach ($this->headings as $field => $label): ?>
            <td><?php if (isset($row[$field])) echo $row[$field] ?></td>
            <?php endforeach; ?>
        </tr>

        <?php $ix++; $on = !$on; endforeach; endif; ?>
    </table>