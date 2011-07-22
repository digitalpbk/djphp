<?php
import("djphp.core.Url");

return array(
	"djphp.contrib.auth.views.AuthController",
	"^login$"              => new Url("login","auth_login"),
	"^logout$"             => new Url("logout","auth_logout"),
    "^register$"           => new Url("register","auth_register"),
	"^forgot-password$"    => new Url("forgot_password","auth_forgot_password"),
	"^forgot-password/mail-sent$"   => new Url("forgot_password_mail_sent","auth_forgot_password_mail_sent"),
	"^reset/(?P<user_id>\d+)/(?P<reset_hash>[a-f0-9]+)$" => new Url("reset_password","auth_reset_password"),
);