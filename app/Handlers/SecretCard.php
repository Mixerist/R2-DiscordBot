<?php

namespace App\Handlers;

use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Exception;
use PDO;
use PDOException;

class SecretCard
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
            $user = $this->getUserByLoginAndPassword(
                $this->interaction->data->options['login']['value'],
                $this->interaction->data->options['password']['value']
            );
            $user_id = $user['mUserNo'];

            $this->checkIfSecretCardEnabled($user_id);

            $this->pdo->beginTransaction();

            $this->deleteOldSecretCardIfExists($user_id);
            $this->generateSecretCard($user_id);
            $this->enableSecretCard($user_id);

            $this->pdo->commit();

            return $this->interaction->respondWithMessage(MessageBuilder::new()->addFileFromContent(trim($user['mUserId']) . '.json', json_encode($this->getSecretCard($user_id), JSON_PRETTY_PRINT)));
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
    private function getUserByLoginAndPassword(string $login, string $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM [FNLAccount].[dbo].[Member] INNER JOIN [FNLAccount].[dbo].[TblUser] ON TblUser.mUserId = Member.mUserId WHERE Member.mUserId = :login AND Member.mUserPswd = :password");
        $stmt->execute([
            ':login' => $login,
            ':password' => $password,
        ]);

        if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $result;
        }

        throw new Exception('Введен неверный логин или пароль.');
    }

    private function generateSecretCard(int $user_id)
    {
        $stmt = $this->pdo->prepare("INSERT INTO [FNLAccount].[dbo].[TblUserSecKeyTable] (mUserNo, mSecKey1, mSecKey2, mSecKey3, mSecKey4, mSecKey5, mSecKey6, mSecKey7, mSecKey8, mSecKey9, mSecKey10, mSecKey11, mSecKey12, mSecKey13, mSecKey14, mSecKey15, mSecKey16, mSecKey17, mSecKey18, mSecKey19, mSecKey20, mSecKey21, mSecKey22, mSecKey23, mSecKey24, mSecKey25, mSecKey26, mSecKey27, mSecKey28, mSecKey29, mSecKey30, mSecKey31, mSecKey32, mSecKey33, mSecKey34, mSecKey35, mSecKey36, mSecKey37, mSecKey38, mSecKey39, mSecKey40, mSecKey41, mSecKey42, mSecKey43, mSecKey44, mSecKey45, mSecKey46, mSecKey47, mSecKey48, mSecKey49, mSecKey50, mSetupDate, mReleaseDate) VALUES (:user_id, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, {$this->generateCode()}, DEFAULT, DEFAULT)");
        $stmt->execute([':user_id' => $user_id]);
    }

    private function deleteOldSecretCardIfExists(int $user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM [FNLAccount].[dbo].[TblUserSecKeyTable] WHERE mUserNo = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        if ($stmt->fetchColumn()) {
            $stmt = $this->pdo->prepare("DELETE FROM [FNLAccount].[dbo].[TblUserSecKeyTable] WHERE TblUserSecKeyTable.mUserNo = :user_id");
            $stmt->execute([':user_id' => $user_id]);

        }
    }

    private function generateCode(): int
    {
        return rand(1000, 9999);
    }

    private function enableSecretCard(int $user_id)
    {
        $stmt = $this->pdo->prepare("UPDATE [FNLAccount].[dbo].[TblUser] SET mSecKeyTableUse = 2 WHERE mUserNo = :user_id");
        $stmt->execute([':user_id' => $user_id]);
    }

    private function getSecretCard(int $user_id)
    {
        $stmt = $this->pdo->prepare("SELECT mSecKey1, mSecKey2, mSecKey3, mSecKey4, mSecKey5, mSecKey6, mSecKey7, mSecKey8, mSecKey9, mSecKey10, mSecKey11, mSecKey12, mSecKey13, mSecKey14, mSecKey15, mSecKey16, mSecKey17, mSecKey18, mSecKey19, mSecKey20, mSecKey21, mSecKey22, mSecKey23, mSecKey24, mSecKey25, mSecKey26, mSecKey27, mSecKey28, mSecKey29, mSecKey30, mSecKey31, mSecKey32, mSecKey33, mSecKey34, mSecKey35, mSecKey36, mSecKey37, mSecKey38, mSecKey39, mSecKey40, mSecKey41, mSecKey42, mSecKey43, mSecKey44, mSecKey45, mSecKey46, mSecKey47, mSecKey48, mSecKey49, mSecKey50 FROM [FNLAccount].[dbo].[TblUserSecKeyTable] WHERE mUserNo = :user_id");
        $stmt->execute([':user_id' => $user_id]);

        $sc = [];

        foreach ($stmt->fetch(PDO::FETCH_ASSOC) as $key => $value) {
            $key = str_replace('mSecKey', '', $key);
            $sc[$key] = $value;
        }

        return $sc;
    }

    /**
     * @throws Exception
     */
    private function checkIfSecretCardEnabled(int $user_id)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM [FNLAccount].[dbo].[TblUserSecKeyTable] INNER JOIN [FNLAccount].[dbo].[TblUser] ON TblUserSecKeyTable.mUserNo = TblUser.mUserNo WHERE TblUser.mUserNo = :user_id AND TblUser.mSecKeyTableUse <> 0");
        $stmt->execute([':user_id' => $user_id]);

        if ($stmt->fetchColumn()) {
            throw new Exception('SC-карта уже была установлена ранее.');
        }
    }
}