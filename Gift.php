<?php

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;

class Gift
{
    private PDO $pdo;

    private Interaction $interaction;

    public function __construct(PDO $pdo, Interaction $interaction)
    {
        $this->pdo = $pdo;
        $this->interaction = $interaction;
    }

    public function run()
    {
        $user = $this->getUser($this->interaction->data->options->pull('login')['value']);
        $promo_code = $this->getPromoCode($this->interaction->data->options->pull('promo_code')['value']);
        $max_character_level = $this->getMaxCharacterLvl($this->interaction->data->options->pull('login')['value']);

        if (!$user) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Пользователь с таким логином не найден.'));
        }

        if (!$promo_code) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Такой промокод не найден или закончился срок его действия.'));
        }

        if ($this->isPromoCodeUsed($user['mUserNo'], $promo_code['id'])) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Ранее вы уже использовали этот промокод.'));
        }

        if ($max_character_level < $promo_code['min_lvl']) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Ваш уровень слишком низок чтобы использовать этот промокод. Наберитесь опыта и попробуйте снова.'));
        }


        $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[TBLSysOrderList] (mAvailablePeriod, mCnt, mItemID, mPracticalPeriod, mSvrNo, mSysID, mUserNo, mBindingType, mLimitedDate, mItemStatus) SELECT items.available_period, items.count, items.item_id, items.practical_period, 2155, promo_codes.sys_id, :user_id, items.binding_type, '2079-06-06', items.item_status FROM [FNLAccount].[dbo].[promo_codes] INNER JOIN [FNLAccount].[dbo].[items] ON promo_codes.id = items.promo_code_id WHERE promo_code = :promo_code");
        $sql->execute([
            'promo_code' => $promo_code['promo_code'],
            'user_id' => $user['mUserNo']
        ]);

        $sql = $this->pdo->prepare("INSERT INTO [FNLAccount].[dbo].[redeemed_promo_codes] (user_id, promo_code_id) VALUES (:user_id, :promo_code_id)");
        $sql->execute([
            'user_id' => $user['mUserNo'],
            'promo_code_id' => $promo_code['id']
        ]);

        return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Промокод успешно активирован! Проверьте свои подарки.'));
    }

    private function getMaxCharacterLvl(string $login)
    {
        $sql = $this->pdo->prepare("SELECT MAX(mLevel) AS mLevel FROM [FNLAccount].[dbo].[TblUser] INNER JOIN [FNLGame2155].[dbo].[TblPc] ON TblUser.mUserNo = TblPc.mOwner INNER JOIN [FNLGame2155].[dbo].[TblPcState] ON TblPc.mNo = TblPcState.mNo WHERE mUserId = :login");
        $sql->execute(['login' => $login]);

        return $sql->fetch(PDO::FETCH_ASSOC)['mLevel'];
    }

    private function getPromoCode(string $promoCode)
    {
        $sql = $this->pdo->prepare("SELECT * FROM [FNLAccount].[dbo].[promo_codes] WHERE promo_code = :promo_code AND is_enabled = 1");
        $sql->execute(['promo_code' => $promoCode]);

        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    private function getUser(string $login)
    {
        $sql = $this->pdo->prepare("SELECT mUserNo FROM [FNLAccount].[dbo].[TblUser] WHERE mUserId = :login");
        $sql->execute(['login' => $login]);

        return $sql->fetch();
    }

    private function isPromoCodeUsed($user_id, $promo_code_id)
    {
        $sql = $this->pdo->prepare("SELECT COUNT (*) FROM [FNLAccount].[dbo].[redeemed_promo_codes] WHERE user_id = :user_id AND promo_code_id = :promo_code_id");
        $sql->execute([
            'user_id' => $user_id,
            'promo_code_id' => $promo_code_id
        ]);

        return $sql->fetchColumn();
    }
}