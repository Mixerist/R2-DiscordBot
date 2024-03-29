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
        $table = $this->table('promo_codes');
        $table->addColumn('sys_id', 'biginteger')
            ->addColumn('server_id', 'smallinteger')
            ->addColumn('promo_code', 'string')
            ->addIndex('promo_code', ['unique' => true])
            ->addColumn('limited_date', 'datetime')
            ->addColumn('max_activations', 'integer')
            ->addColumn('min_lvl', 'smallinteger')
            ->addColumn('max_lvl', 'smallinteger')
            ->addColumn('specific_for_guild', 'smallinteger')
            ->addColumn('hours_before_use', 'smallinteger')
            ->addColumn('is_enabled', 'boolean')
            ->create();
    }
}
