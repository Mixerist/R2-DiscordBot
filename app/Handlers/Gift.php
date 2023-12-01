<?php

namespace App\Handlers;

use Classes\Database;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Exception;
use PDO;
use PDOException;
use React\Promise\ExtendedPromiseInterface;

class Gift
{
    private PDO $pdo;

    private Interaction $interaction;

    private int $user_id;

    private array $promo_code = [];

    private int $max_character_level;

    public function __construct(Interaction $interaction)
    {
        $this->pdo = Database::getInstance();
        $this->interaction = $interaction;
    }

    public function run(): ExtendedPromiseInterface
    {
        try {
            $this->user_id = $this->getUserId();
            $this->promo_code = $this->getPromoCode();
            $this->max_character_level = $this->getMaxCharacterLvl();

            $this->isPromoCodeUsed();

            if ($this->promo_code['max_activations'] > 0) {
                $this->checkCountOfActivations();
            }

            if ($this->promo_code['specific_for_guild'] > 0) {
                $this->checkGuild();
            }

            if ($this->promo_code['min_lvl'] > 0) {
                $this->checkMinLvl();
            }

            if ($this->promo_code['max_lvl'] > 0) {
                $this->checkMaxLvl();
            }

            $this->pdo->beginTransaction();
            $this->markPromoCodeAsActivated();
            $this->giveOutItems();
            $this->pdo->commit();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Промокод успешно активирован! Проверьте свои подарки.'));
        } catch (PDOException $e) {
            $this->pdo->rollBack();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Что-то пошло не так. Попробуйте снова.'));
        } catch (Exception $e) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent($e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    private function getUserId()
    {
        $sql = $this->pdo->prepare("SELECT mUserNo FROM [FNLAccount].[dbo].[TblUser] WHERE mUserId = :login");
        $sql->execute(['login' => $this->interaction->data->options['login']['value']]);

        if ($result = $sql->fetch()['mUserNo']) {
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

        if ($result = $sql->fetch()) {
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

        if ($max_character_lvl = $sql->fetch()['mLevel']) {
            return $max_character_lvl;
        }

        throw new Exception('Не найдено ни одного персонажа на сервере. Создайте персонажа и попробуйте снова.');
    }

    /**
     * @throws Exception
     */
    private function checkCountOfActivations()
    {
        $sql = $this->pdo->prepare("SELECT COUNT(*) FROM [FNLBilling].[dbo].[redeemed_promo_codes] WHERE redeemed_promo_codes.promo_code_id = :promo_code_id");
        $sql->execute([
            'promo_code_id' => $this->promo_code['id'],
        ]);

        if ($sql->fetchColumn() >= $this->promo_code['max_activations']) {
            throw new Exception('Превышено максимальное количество активаций для данного купона.');
        }
    }

    private function markPromoCodeAsActivated()
    {
        $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[redeemed_promo_codes] (user_id, promo_code_id) VALUES (:user_id, :promo_code_id)");
        $sql->execute([
            'user_id' => $this->user_id,
            'promo_code_id' => $this->promo_code['id']
        ]);
    }

    private function giveOutItems()
    {
        $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[TBLSysOrderList] (mAvailablePeriod, mCnt, mItemID, mPracticalPeriod, mSvrNo, mSysID, mUserNo, mBindingType, mLimitedDate, mItemStatus) SELECT items.available_period, items.count, items.item_id, items.practical_period, promo_codes.server_id, promo_codes.sys_id, :user_id, items.binding_type, promo_codes.limited_date, items.item_status FROM [FNLBilling].[dbo].[promo_codes] INNER JOIN [FNLBilling].[dbo].[items] ON promo_codes.id = items.promo_code_id WHERE promo_code = :promo_code");
        $sql->execute([
            'promo_code' => $this->promo_code['promo_code'],
            'user_id' => $this->user_id
        ]);
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
    private function getGuild()
    {
        $server = $this->defineServer();

        $sql = $this->pdo->prepare("SELECT * FROM [{$server['database_name']}].[dbo].[TblGuild] WHERE TblGuild.mGuildNo = :guild_id");
        $sql->execute([
            'guild_id' => $this->promo_code['specific_for_guild'],
        ]);

        $result = $sql->fetch();

        if ($result) {
            return $result;
        }

        throw new Exception('Несуществующая гильдия.');
    }

    /**
     * @throws Exception
     */
    private function checkGuild()
    {
        $server = $this->defineServer();
        $guild = $this->getGuild();

        $sql = $this->pdo->prepare("SELECT DATEADD(hh, {$this->promo_code['hours_before_use']}, MIN(TblGuildMember.mRegDate)) AS mRegDate FROM [FNLAccount].[dbo].[TblUser] INNER JOIN [{$server['database_name']}].[dbo].[TblPc] ON TblUser.mUserNo = TblPc.mOwner INNER JOIN [{$server['database_name']}].[dbo].[TblGuildMember] ON TblPc.mNo = TblGuildMember.mPcNo WHERE TblGuildMember.mGuildNo = :guild_id AND TblUser.mUserNo = :user_id");
        $sql->execute([
            'guild_id' => $this->promo_code['specific_for_guild'],
            'user_id' => $this->user_id
        ]);

        $date = $sql->fetchColumn();

        if (empty($date)) {
            throw new Exception('Только участники гильдии ' . trim($guild['mGuildNm']) . ' могут использовать данный промокод.');
        }

        if (time() < strtotime($date)) {
            throw new Exception("Вы сможете активировать данный промокод не раньше чем через {$this->promo_code['hours_before_use']} ч. после вступления в гильдию. Попробуйте после " . date('Y-m-d H:i', strtotime($date)));
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
