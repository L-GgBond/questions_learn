<?php

namespace App\Service;
use App\Exception\BusinessException;
use App\Model\User;
use App\Model\UserDynamic;
use Hyperf\DbConnection\Db;

class UserService
{
    public function __construct(
        private User $user,
        private JWTService $jwtService,
    ){
    }

    /**
     * 根据邮箱获取用户信息
     * @param $email
     * @return array|null 找不到时返回 null 而不是报错
     */
    public function getUserByEmail($email): ?array
    {
        $user = $this->user->where('email', $email)->first();
        return $user ? $user->toArray() : null;
    }

    /**
     * 登陆
     * @param array $credentials
     * @return array
     */
    public function login(array $credentials): array
    {
        $user = $this->getUserByEmail($credentials['email']);

        // 严谨校验：合并判断条件，防止执行不必要的 password_verify 消耗 CPU
        if (!$user || !password_verify($credentials['password'], $user['password'])) {
            // 统一抛出 401 状态码更符合 HTTP 语义
            throw new BusinessException('账号或密码错误', 401);
        }

        // 校验通过，签发 Token
        return $this->jwtService->generateToken((int) $user['id']);

    }

    /**
     * 注册新用户
     *
     * @param array $data 验证后的有效数据
     * @return User 返回创建的用户对象，方便后续使用
     * @throws Throwable 抛出异常交由外层（或全局异常处理器）处理
     */
    public function signUp(array $data): User
    {
        // 使用闭包事务，框架会自动把控 commit 和 rollback，避免手动处理的遗漏
        return Db::transaction(function() use ($data) {
            $user = new User();
            $user->email = $data['email'];
            $user->password = password_hash($data['password'], PASSWORD_DEFAULT);

            // 使用 random_int 替代 rand，提供更好的随机性分配
            $user->pic = sprintf('images/avatar/%d.png', random_int(1, 6));
            $user->nickname = sprintf('api_%d%s', random_int(1, 99), date('Hi'));

            // 严谨校验：保存失败应主动抛出异常阻断事务
            if (!$user->save()) {
                throw new BusinessException('User creation failed.');
            }

            // 同步创建动态表记录
            $dynamicModel = new UserDynamic();
            $dynamicModel->uid = $user->id;

            if (!$dynamicModel->save()) {
                throw new BusinessException('User dynamic initialization failed.');
            }

            return $user;
        });

        /**
        DB::beginTransaction();
        try {
            $model = new User();
            $model->email = $data['email'];
            $model->password = password_hash($data['password'],PASSWORD_DEFAULT);
            $model->pic = 'images/avatar/' . rand(1, 10) . '.jpg';
            $model->nickname = 'api_' . rand(1, 99) . date('Hi');
            $model->save();

            // 同步
            $dynamicModel = new UserDynamic();
            $dynamicModel->uid = $model->id;
            $dynamicModel->save();
            Db::commit();


        }catch (\Exception $e){
            DB::rollBack();
        }
         **/
    }
}