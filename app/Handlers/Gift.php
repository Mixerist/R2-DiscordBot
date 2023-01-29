<?php

namespace App\Handlers;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Exception;
use PDO;
use PDOException;

class Gift
{
    private PDO $pdo;

    private Interaction $interaction;

    private int $user_id;

    private array $promo_code = [];

    private int $max_character_level;

    public function __construct(PDO $pdo, Interaction $interaction)
    {
        $this->pdo = $pdo;
        $this->interaction = $interaction;
    }

    public function run()
    {
        try {
            $this->user_id = $this->getUserId();
            $this->promo_code = $this->getPromoCode();
            $this->max_character_level = $this->getMaxCharacterLvl();

            $this->isPromoCodeUsed();

            if ($this->promo_code['min_lvl'] > 0) {
                $this->checkMinLvl();
            }

            if ($this->promo_code['max_lvl'] > 0) {
                $this->checkMaxLvl();
            }

        } catch (Exception $e) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent($e->getMessage()));
        }

        try {
            $this->pdo->beginTransaction();

            $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[redeemed_promo_codes] (user_id, promo_code_id) VALUES (:user_id, :promo_code_id)");
            $sql->execute([
                'user_id' => $this->user_id,
                'promo_code_id' => $this->promo_code['id']
            ]);

            $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[TBLSysOrderList] (mAvailablePeriod, mCnt, mItemID, mPracticalPeriod, mSvrNo, mSysID, mUserNo, mBindingType, mLimitedDate, mItemStatus) SELECT items.available_period, items.count, items.item_id, items.practical_period, promo_codes.server_id, promo_codes.sys_id, :user_id, items.binding_type, promo_codes.limited_date, items.item_status FROM [FNLBilling].[dbo].[promo_codes] INNER JOIN [FNLBilling].[dbo].[items] ON promo_codes.id = items.promo_code_id WHERE promo_code = :promo_code");
            $sql->execute([
                'promo_code' => $this->promo_code['promo_code'],
                'user_id' => $this->user_id
            ]);

            $this->pdo->commit();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Промокод успешно активирован! Проверьте свои подарки.'));
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Что-то пошло не так. Попробуйте снова.'));
        }
    }

    /**
     * @throws Exception
     */
    private function getUserId()
    {
        $sql = $this->pdo->prepare("SELECT mUserNo FROM [FNLAccount].[dbo].[TblUser] WHERE mUserId = :login");
        $sql->execute(['login' => $this->interaction->data->options['login']['value']]);

        if ($result = $sql->fetch(PDO::FETCH_ASSOC)['mUserNo']) {
            return $result;
        }

        throw new Exception('Пользователь с таким логином не найден.');
    }

    /**
     * @throws Exception
     */
    private function getPromoCode()
    {
        $sql = $this->pdo->prepare("SELECT * FROM [FNLBilling].[dbo].[promo_codes] WHERE promo_code = :promo_code AND limited_date > GETDATE() AND is_enabled = 1");
        $sql->execute(['promo_code' => $this->interaction->data->options['promo_code']['value']]);

        if ($result = $sql->fetch(PDO::FETCH_ASSOC)) {
            return $result;
        }

        throw new Exception('Такой промокод не найден или закончился срок его действия.');
    }

    /**
     * @throws Exception
     */
    private function getMaxCharacterLvl()
    {
        $server = $this->defineServer();

        $sql = $this->pdo->prepare("SELECT MAX(mLevel) AS mLevel FROM [FNLAccount].[dbo].[TblUser] INNER JOIN [{$server['database_name']}].[dbo].[TblPc] ON TblUser.mUserNo = TblPc.mOwner INNER JOIN [{$server['database_name']}].[dbo].[TblPcState] ON TblPc.mNo = TblPcState.mNo WHERE mUserId = :login AND TblPc.mDelDate IS NULL");
        $sql->execute(['login' => $this->interaction->data->options['login']['value']]);

        if ($max_character_lvl = $sql->fetch(PDO::FETCH_ASSOC)['mLevel']) {
            return $max_character_lvl;
        }

        throw new Exception('Не найдено ни одного персонажа на сервере. Создайте персонажа и попробуйте снова.');
    }

    /**
     * @throws Exception
     */
    private function defineServer()
    {
        $server_id = $this->promo_code['server_id'];

        foreach (config()['servers'] as $server) {
            if ($server['server_id'] == $server_id) {
                return $server;
            }
        }

        throw new Exception('Server not found.');
    }

    /**
     * @throws Exception
     */
    private function isPromoCodeUsed()
    {
        $sql = $this->pdo->prepare("SELECT COUNT(*) FROM [FNLBilling].[dbo].[redeemed_promo_codes] WHERE user_id = :user_id AND promo_code_id = :promo_code_id");
        $sql->execute([
            'user_id' => $this->user_id,
            'promo_code_id' => $this->promo_code['id']
        ]);

        if ($sql->fetchColumn()) {
            throw new Exception('Ранее вы уже использовали этот промокод.');
        }
    }

    /**
     * @throws Exception
     */
    private function checkMinLvl()
    {
        if ($this->max_character_level < $this->promo_code['min_lvl']) {
            throw new Exception("Ваш уровень слишком низок чтобы использовать этот промокод. Минимальный уровень использования: {$this->promo_code['min_lvl']}.");
        }
    }

    /**
     * @throws Exception
     */
    private function checkMaxLvl()
    {
        if ($this->max_character_level > $this->promo_code['max_lvl']) {
            throw new Exception("Ваш уровень слишком высок чтобы использовать этот промокод. Максимальный уровень использования: {$this->promo_code['max_lvl']}.");
        }
    }
}
