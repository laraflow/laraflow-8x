<?php

namespace App\Services\Backend\Auth;

use function __;
use App\Models\Setting\User;
use App\Repositories\Eloquent\Backend\Setting\UserRepository;
use App\Services\Backend\Common\FileUploadService;
use App\Supports\Constant;
use App\Supports\Utility;
use Exception;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class RegisteredUserService
{
    /**
     * @var UserRepository
     */
    public $userRepository;

    /**
     * @var FileUploadService
     */
    public $fileUploadService;

    /**
     * RegisteredUserService constructor.
     *
     * @param  UserRepository  $userRepository
     * @param  FileUploadService  $fileUploadService
     */
    public function __construct(UserRepository $userRepository,
        FileUploadService $fileUploadService)
    {
        $this->userRepository = $userRepository;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * @param  array  $registerFormInputs
     * @return array
     *
     * @throws Exception
     */
    public function attemptRegistration(array $registerFormInputs): ?array
    {
        \DB::beginTransaction();
        //format request object
        $inputs = $this->formatRegistrationInfo($registerFormInputs);
        try {
            //create new user
            $newUser = $this->userRepository->create($inputs);
            if ($newUser instanceof User) {
                if ($this->attachAvatarImage($newUser) && $this->attachDefaultRoles($newUser)) {
                    \DB::commit();
                    $newUser->refresh();

                    Auth::login($newUser);

                    return ['status' => true, 'message' => __('auth.register.success'), 'level' => config('constant.msg_toastr_success'), 'title' => 'Authentication'];
                } else {
                    return ['status' => false, 'message' => __('auth.register.failed'), 'level' => config('constant.msg_toastr_warning'), 'title' => 'Alert!'];
                }
            } else {
                return ['status' => false, 'message' => 'User model creation failed', 'level' => config('constant.msg_toastr_error'), 'title' => 'Error!'];
            }
        } catch (\Exception $exception) {
            $this->userRepository->handleException($exception);

            return ['status' => false, 'message' => __($exception->getMessage()), 'level' => config('constant.msg_toastr_error'), 'title' => 'Error!'];
        }
    }

    /**
     * @param  array  $request
     * @return array
     *
     * @throws Exception
     */
    private function formatRegistrationInfo(array $request): array
    {
        //Hash password
        return [
            'name' => $request['name'],
            'password' => Utility::hashPassword(($request['password'] ?? config('constant.password'))),
            'username' => ($request['username'] ?? Utility::generateUsername($request['name'])),
            'mobile' => ($request['mobile'] ?? null),
            'email' => ($request['email'] ?? null),
            'remarks' => 'self-registered',
            'enabled' => config('constant.enabled_option'),
        ];
    }

    /**
     * @param  User  $user
     * @return bool
     *
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws Exception
     */
    protected function attachAvatarImage(User $user): bool
    {
        //add profile image
        $profileImagePath = $this->fileUploadService->createAvatarImageFromText($user->name);
        $user->addMedia($profileImagePath)->toMediaCollection('avatars');

        return $user->save();
    }

    /**
     * @param  User  $user
     * @return bool
     */
    protected function attachDefaultRoles(User $user): bool
    {
        $this->userRepository->setModel($user);

        return $this->userRepository->manageRoles([config('constant.guest_role_id')]);
    }
}
