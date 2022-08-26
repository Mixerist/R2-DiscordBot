<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePromoCodesTable extends AbstractMigration
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
        $table = $this->table('promo_codes', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'biginteger')
            ->addColumn('sys_id', 'biginteger')
            ->addColumn('server_id', 'smallinteger')
            ->addColumn('promo_code', 'string')
            ->addIndex('promo_code', ['unique' => true])
            ->addColumn('limited_date', 'datetime')
            ->addColumn('min_lvl', 'smallinteger')
            ->addColumn('is_enabled', 'boolean')
            ->create();
    }
}
