<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateItemsTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('items', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger')
            ->addColumn('promo_code_id', 'biginteger')
            ->addForeignKey('promo_code_id', 'promo_codes', 'id')
            ->addColumn('item_id', 'integer')
            ->addColumn('count', 'integer')
            ->addColumn('available_period', 'integer')
            ->addColumn('practical_period', 'integer')
            ->addColumn('binding_type', 'boolean')
            ->addColumn('item_status', 'smallinteger')
            ->create();
    }
}
