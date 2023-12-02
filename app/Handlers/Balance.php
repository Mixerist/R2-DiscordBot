<?php

namespace App\Handlers;

use Classes\Database;
use Discord\Builders\MessageBuilder;
use Exception;
use PDO;
use PDOException;
use React\Promise\ExtendedPromiseInterface;

class Balance extends AbstractHandler
{
    private PDO $pdo;

    private string $primary_key = 'mUserNo';

    public function run(): ExtendedPromiseInterface
    {
        $this->pdo = Database::getInstance();

        try {
            $member = $this->getMember($this->getParam('login'));
            $coupon = $this->getCoupon($this->getParam('coupon'));

            $this->pdo->beginTransaction();
            $this->markCouponAsActivated($member[$this->primary_key], $coupon['id']);
            $this->increaseMemberBalance($member[$this->primary_key], $coupon['denomination']);
            $this->pdo->commit();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->setContent('Купон успешно активирован! Проверьте свой баланс.'));
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
    private function getMember(string $login)
    {
        $sql = $this->pdo->prepare("SELECT * FROM [FNLAccount].[dbo].[Member] WHERE mUserId = :login");
        $sql->execute(['login' => $login]);

        if ($result = $sql->fetch()) {
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

        if ($result = $sql->fetch()) {
            return $result;
        }

        throw new Exception('Такой купон не найден или уже был использован ранее.');
    }

    private function increaseMemberBalance(int $uid, int $denomination)
    {
        $sql = $this->pdo->prepare("UPDATE [FNLAccount].[dbo].[Member] SET Cash += :denomination WHERE {$this->primary_key} = :uid");
        $sql->execute([
            'denomination' => $denomination,
            'uid' => $uid
        ]);
    }

    private function markCouponAsActivated(int $uid, int $coupon_id)
    {
        $sql = $this->pdo->prepare("INSERT INTO [FNLBilling].[dbo].[redeemed_coupons] (member_uid, coupon_id) VALUES (:uid, :coupon_id)");
        $sql->execute([
            'uid' => $uid,
            'coupon_id' => $coupon_id
        ]);
    }
}
