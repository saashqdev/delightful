<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\GenderType;
use App\Domain\Contact\Entity\ValueObject\UserIdType;
use App\Domain\Contact\Entity\ValueObject\UserStatus;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Constants\SmsSceneType;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SmsTypeEnum;
use App\Infrastructure\ExternalAPI\Sms\SmsStruct;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Random\RandomException;
use RedisException;
use Swow\Exception;
use Throwable;

use function Hyperf\Config\config;

class DelightfulAccountDomainService extends AbstractContactDomainService
{
    public function getAccountByDelightfulIds(array $delightfulIds): array
    {
        return $this->accountRepository->getAccountByDelightfulIds($delightfulIds);
    }

    /**
     * @throws RedisException
     * @throws RandomException
     */
    public function sendVerificationCode(string $stateCode, string $phone, string $type): array
    {
        // hand机numberwhethercanusecheck
        $this->checkPhoneStatus($type, $stateCode, $phone);
        // 短信frequencycheck
        $this->checkSmsLimit($stateCode, $phone);
        $code = (string) random_int(100000, 999999);
        $variables = ['timeout' => 10, 'verification_code' => $code];
        $sign = SignEnum::DENG_TA;
        // will业务场景type 转for 短信type
        $smsType = match ($type) {
            SmsSceneType::BIND_PHONE,
            SmsSceneType::CHANGE_PHONE,
            SmsSceneType::CHANGE_PASSWORD,
            SmsSceneType::REGISTER_ACCOUNT,
            SmsSceneType::ACCOUNT_LOGIN_ACTIVE => SmsTypeEnum::VERIFICATION_WITH_EXPIRATION->value,
            default => ''
        };
        // according to type certain短信templateid
        $templateId = $this->template->getTemplateIdByTypeAndLanguage($smsType, LanguageEnum::ZH_CN->value);
        $sms = new SmsStruct($stateCode . $phone, $variables, $sign, $templateId);
        $sendResult = $this->sms->send($sms);
        //        $sendResult = new SendResult();
        //        $sendResult->setResult(0, $code);
        $key = $this->getSmsVerifyCodeKey($stateCode . $phone, $type);
        // cacheverify码,back续verifyuse
        $this->redis->setex($key, 10 * 60, $code);
        return $sendResult->toArray();
    }

    /**
     * @throws RedisException
     */
    public function humanRegister(string $stateCode, string $phone, string $verifyCode, string $password): array
    {
        // hand机numbercheck
        $this->checkPhoneStatus(SmsSceneType::REGISTER_ACCOUNT, $stateCode, $phone);
        $key = $this->getSmsVerifyCodeKey($stateCode . $phone, SmsSceneType::REGISTER_ACCOUNT);
        $code = $this->redis->get($key);
        if ($code === false) {
            ExceptionBuilder::throw(UserErrorCode::VERIFY_CODE_HAS_EXPIRED);
        }
        if ($code !== $verifyCode) {
            ExceptionBuilder::throw(UserErrorCode::VERIFY_CODE_ERROR);
        }
        $userId = $this->idGenerator->generate();
        $time = date('Y-m-d H:i:s');

        // useSHA256encrypt密码
        $hashedPassword = $this->passwordService->hashPassword($password);

        $this->userRepository->insertUser([
            'id' => $userId,
            'state_code' => $stateCode,
            'mobile' => $phone,
            'password' => $hashedPassword,
            'user_type' => 0,
            'status' => 0,
            'gender' => 0,
            'description' => '',
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        return ['user_id' => $userId];
    }

    public function addUserAndAccount(DelightfulUserEntity $userDTO, AccountEntity $accountDTO): void
    {
        // 判断账numberwhether存in
        $delightfulId = $accountDTO->getDelightfulId();
        if (empty($delightfulId) || empty($userDTO->getOrganizationCode())) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }
        $existsAccount = $this->accountRepository->getAccountInfoByDelightfulId($delightfulId);
        if ($existsAccount !== null) {
            $userEntity = $this->userRepository->getUserByAccountAndOrganization($delightfulId, $userDTO->getOrganizationCode());
            // 账number存in,andintheorganizationdownalready经generateuserinfo,直接return
            if ($userEntity !== null) {
                $userDTO->setUserId($userEntity->getUserId());
                $userDTO->setNickname($userEntity->getNickname());
                $userDTO->setUserType(UserType::Human);
                return;
            }
        }
        // addlock防止andhair
        $key = sprintf('addUserAndAccount:%s', $delightfulId);
        if (! $this->locker->mutexLock($key, $delightfulId, 5)) {
            ExceptionBuilder::throw(UserErrorCode::CREATE_USER_TOO_FREQUENTLY);
        }
        Db::beginTransaction();
        try {
            if (! $existsAccount) {
                // 账numbernot存in,new账number
                $accountEntity = $this->accountRepository->createAccount($accountDTO);
            } else {
                // 账number存in,butistheorganizationdownnothaveuserinfo
                $accountEntity = $existsAccount;
            }
            // willgenerate账numberinfoassociatetouserEntity
            $userDTO->setDelightfulId($accountEntity->getDelightfulId());
            $userEntity = $this->userRepository->getUserByAccountAndOrganization($delightfulId, $userDTO->getOrganizationCode());
            if ($userEntity && $userEntity->getUserId()) {
                $userDTO->setUserId($userEntity->getUserId());
                return;
            }
            // generateorganizationdownuserinfo
            if (empty($userDTO->getUserId())) {
                // certainuser_idgeneraterule
                $userId = $this->userRepository->getUserIdByType(UserIdType::UserId, $userDTO->getOrganizationCode());
                $userDTO->setUserId($userId);
                // 1.47x(10**-29) 概ratedown,user_idwillduplicate,willbemysql唯oneindex拦截,letuser重新loginonetimethenline.
                $this->userRepository->createUser($userDTO);
            }
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        } finally {
            $this->locker->release($key, $delightfulId);
        }
    }

    /**
     * @param AccountEntity $accountDTO supportenable/disable智canbody
     * @throws Throwable
     */
    public function aiRegister(DelightfulUserEntity $userDTO, DataIsolation $dataIsolation, AccountEntity $accountDTO): DelightfulUserEntity
    {
        Db::beginTransaction();
        try {
            if (empty($userDTO->getNickname()) || empty($userDTO->getDescription())) {
                ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR);
            }
            $accountEntity = $this->accountRepository->getAccountInfoByAiCode($accountDTO->getAiCode());
            if ($accountEntity) {
                $userDTO->setDelightfulId($accountEntity->getDelightfulId());
                // update ai 昵称
                $accountEntity->setRealName($userDTO->getNickname());
                // update账numberinfo
                if ($accountDTO->getStatus() !== null) {
                    // enable/disable智canbody
                    $accountEntity->setStatus($accountDTO->getStatus());
                }
                $this->accountRepository->saveAccount($accountEntity);
                // update账numberintheorganizationdownuserinfo
                $userEntity = $this->userRepository->getUserByAccountAndOrganization($accountEntity->getDelightfulId(), $dataIsolation->getCurrentOrganizationCode());
                if ($userEntity === null) {
                    # 账number存in,butistheorganizationdownnothaveuserinfo. generateuserinfo
                    $userEntity = $this->createUser($userDTO, $dataIsolation);
                } else {
                    // 账numberanduserinfoall存in,updateonedownuserinfo
                    $userEntity->setNickname($userDTO->getNickname());
                    $userEntity->setAvatarUrl($userDTO->getAvatarUrl());
                    $userEntity->setDescription($userDTO->getDescription());
                    $this->userRepository->saveUser($userEntity);
                }
                Db::commit();
                return $userEntity;
            }
            // create账number
            $extra = [
                'like_num' => 0,
                'friend_num' => 0,
                'label' => $userDTO->getLabel(),
                'description' => $userDTO->getDescription(),
            ];
            $accountDTO->setExtra(Json::encode($extra));
            $accountDTO->setCountryCode('+86');
            $accountDTO->setEmail('');
            $accountDTO->setGender(GenderType::Unknown);
            $accountDTO->setPhone($accountDTO->getAiCode());
            $accountDTO->setType(UserType::Ai);
            # 账numbernot存in(user肯定alsonot存in),generate账numberanduserinfo
            $delightfulId = (string) IdGenerator::getSnowId();
            $accountDTO->setDelightfulId($delightfulId);
            $this->accountRepository->createAccount($accountDTO);
            $userDTO->setDelightfulId($delightfulId);
            // for账numberincurrentorganizationcreateuser
            $result = $this->createUser($userDTO, $dataIsolation);
            Db::commit();
            return $result;
        } catch (Exception $exception) {
            Db::rollBack();
            $this->logger->error('aiRegister error: ' . $exception->getMessage());
            throw $exception;
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
    }

    public function getAccountInfoByDelightfulId(string $delightfulId): ?AccountEntity
    {
        return $this->accountRepository->getAccountInfoByDelightfulId($delightfulId);
    }

    public function getAccountInfoByAiCode(string $aiCode): ?AccountEntity
    {
        return $this->accountRepository->getAccountInfoByAiCode($aiCode);
    }

    public function searchUserByPhoneOrRealName(string $query, DataIsolation $dataIsolation): array
    {
        $accounts = $this->accountRepository->searchUserByPhoneOrRealName($query);
        if (empty($accounts)) {
            return [];
        }
        $delightfulIds = array_column($accounts, 'delightful_id');
        return $this->userRepository->getUserByAccountsAndOrganization($delightfulIds, $dataIsolation->getCurrentOrganizationCode());
    }

    /**
     * @return AccountEntity[]
     */
    public function getByDelightfulIds(array $delightfulIds): array
    {
        return $this->accountRepository->getAccountByDelightfulIds($delightfulIds);
    }

    public function getByDelightfulId(string $delightfulId): ?AccountEntity
    {
        return $this->accountRepository->getAccountInfoByDelightfulId($delightfulId);
    }

    /**
     * modifyuser密码
     */
    public function updatePassword(string $delightfulId, string $plainPassword): bool
    {
        if (! $this->getAccountInfoByDelightfulId($delightfulId)) {
            return false;
        }

        // useSHA256encrypt密码
        $hashedPassword = $this->passwordService->hashPassword($plainPassword);

        // update密码
        $this->accountRepository->updateAccount($delightfulId, [
            'password' => $hashedPassword,
        ]);

        return true;
    }

    private function createUser(DelightfulUserEntity $userDTO, DataIsolation $dataIsolation): DelightfulUserEntity
    {
        $userId = $this->userRepository->getUserIdByType(UserIdType::UserId, $dataIsolation->getCurrentOrganizationCode());
        $userDTO->setUserId($userId);
        $userDTO->setUserType(UserType::Ai);
        $userDTO->setLikeNum(0);
        $userDTO->setStatus(UserStatus::Activated);
        $userDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        return $this->userRepository->createUser($userDTO);
    }

    private function getSmsDayCountKey(string $stateCodePhoneNumber): string
    {
        return 'sms.day_count.' . $stateCodePhoneNumber;
    }

    private function getSmsLastSendTimeKey(string $stateCodePhoneNumber): string
    {
        return 'sms.last_send_time.' . $stateCodePhoneNumber;
    }

    private function getSmsVerifyCodeKey(string $stateCodePhoneNumber, string $type): string
    {
        return 'sms.verify_code.' . $stateCodePhoneNumber . $type;
    }

    private function checkPhoneStatus(string $type, string $stateCode, string $phone): void
    {
        $mobile = $this->userRepository->getUserByMobileWithStateCode($stateCode, $phone);
        if ($mobile && in_array($type, [SmsSceneType::REGISTER_ACCOUNT, SmsSceneType::BIND_PHONE], true)) {
            ExceptionBuilder::throw(UserErrorCode::PHONE_HAS_REGISTER);
        }
        if (! $mobile && in_array($type, [SmsSceneType::CHANGE_PHONE, SmsSceneType::CHANGE_PASSWORD], true)) {
            ExceptionBuilder::throw(UserErrorCode::PHONE_NOT_BIND_USER);
        }
    }

    /**
     * @throws RedisException
     */
    private function checkSmsLimit(string $stateCode, string $phone): void
    {
        // 短信sendfrequency控制
        $timeInterval = config('sms.time_interval') ?: 60;
        $lastSendTimeKey = $this->getSmsLastSendTimeKey($stateCode . $phone);
        $setSuccess = $this->redis->set($lastSendTimeKey, '1', ['nx', 'ex' => $timeInterval]);
        if (! $setSuccess) {
            ExceptionBuilder::throw(UserErrorCode::SMS_RATE_LIMIT);
        }
        $dayMaxCountKey = $this->getSmsDayCountKey($stateCode . $phone);
        $count = (int) $this->redis->get($dayMaxCountKey);
        $dayMaxCount = config('sms.day_max_count') ?: 30;
        if ($count > $dayMaxCount) {
            ExceptionBuilder::throw(UserErrorCode::SMS_RATE_LIMIT);
        }
    }
}
