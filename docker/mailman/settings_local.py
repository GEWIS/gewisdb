# This configuration file only applies the second time the container is started
DEBUG = False

ADMINS = ()

ACCOUNT_AUTHENTICATION_METHOD = "username"
ACCOUNT_EMAIL_REQUIRED = False
ACCOUNT_EMAIL_VERIFICATION = "none"

MAILMAN_WEB_SOCIAL_AUTH = [
    'allauth.socialaccount.providers.dummy',
]
SOCIALACCOUNT_PROVIDERS = {
    'dummy': {},
}