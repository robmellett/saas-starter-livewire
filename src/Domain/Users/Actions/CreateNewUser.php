<?php

namespace Domain\Users\Actions;

use App\Models\User;
use Domain\Users\Data\RegisterUserData;
use Domain\Workspaces\Actions\CreatePersonalWorkspaceAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        private readonly CreatePersonalWorkspaceAction $createPersonalWorkspace,
    ) {}

    /**
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $data = RegisterUserData::validateAndCreate($input);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            $this->createPersonalWorkspace->execute($user);

            return $user->refresh();
        });
    }
}
