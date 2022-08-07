<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index();
            $table->integer('state_id')->nullable()->unsigned()->default(null);
            $table->string('name');
            $table->string('email')->nullable()->unique()->collation('utf8mb4_bin')->index();
            $table->string('username')->nullable()->unique()->collation('utf8mb4_bin')->index();
            $table->string('mobile')->nullable()->unique()->collation('utf8mb4_bin')->index();
            $table->string('password')->index();
            $table->boolean('force_pass_reset')->default(false);
            $table->string('remarks')->nullable();
            $table->string('locale', 10)->default(config('app.locale'));
            $table->string('home_page')->default(config('constant.dashboard_route'))->nullable();
            $table->enum('enabled', array_keys(config('constant.enabled_options')))
                ->default(config('constant.enabled_option'))->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->blameable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
