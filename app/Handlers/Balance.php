<?php

namespace App\Handlers;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Exception;
use PDO;

class Balance
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
        try {
            $member = $this->getMember($this->interaction->data->options['login']['value']);
            $coupon = $this->getCoupon($this->interaction->data->options['coupon']['value']);
        } catch (Exception $e) {
            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent($e->getMessage()));
        }

        try {
            $this->pdo->beginTransaction();

            $this->markCouponAsActivated($member['uid'], $coupon['id']);
            $this->increaseUserBalance($member['uid'], $coupon['denomination']);

            $this->pdo->commit();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Купон успешно активирован! Проверьте свой баланс.'));
        } catch (\PDOException $e) {
            $this->pdo->rollBack();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Что-то пошло не так. Попробуйте снова.'));
        }
    }

    /**
     * @throws Exception
     */
    private function getMember(string $login)
    {
        $sql = $this->pdo->prepare("SELECT * FROM [FNLAccount].[dbo].[Member] WHERE mUserId = :login");
        $sql->execute(['login' => $login]);

        if ($result = $sql->fetch(PDO::FETCH_ASSOC)) {
            return $result;
        }

        throw new Exception('Пользователь с таким логином не найден.');
    }

    /**
     * @throws Exception
     */
    private function getCoupon(string $coupon)
    {
        $sql = $this->pdo->prepare("SELECT coupons.* FROM [FNLBilling].[dbo].[coupons] LEFT JOIN [FNLBilling].[dbo].[redeemed_coupons] ON coupons.id = redeemed_coupons.coupon_id WHERE coupon = :coupon AND coupon_id IS NULL");
        $sql->execute(['coupon' => $coupon]);

        if ($result = $sql->fetch(PDO::FETCH_ASSOC)) {
            return $result;
        }

        throw new Exception('Такой купон не найден или уже был использован ранее.');
    }

    private function increaseUserBalance($uid, $denomination)
    {
        $sql = $this->pdo->prepare("UPDATE [FNLAccount].[dbo].[Member] SET Cash += :denomination WHERE uid = :uid");
        $sql->execute([
            'denomination' => $denomination,
            'uid' => $uid
        ]);
    }

    private function markCouponAsActivated($uid, $coupon_id)
    {
        $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[redeemed_coupons] (member_uid, coupon_id) VALUES (:uid, :coupon_id)");
        $sql->execute([
            'uid' => $uid,
            'coupon_id' => $coupon_id
        ]);
    }
}