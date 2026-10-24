#!/bin/sh
# Railway (and any production/staging boot) must not stay in Laravel's diagnostic defaults.
# Local machines without Railway variables are left on whatever APP_ENV they set.

launch_mode_apply() {
    railway=0
    if [ -n "${RAILWAY_ENVIRONMENT_NAME:-}" ] || [ -n "${RAILWAY_ENVIRONMENT:-}" ] || [ -n "${RAILWAY_PROJECT_ID:-}" ]; then
        railway=1
    fi

    name=$(printf '%s' "${RAILWAY_ENVIRONMENT_NAME:-${RAILWAY_ENVIRONMENT:-}}" | tr '[:upper:]' '[:lower:]')

    if [ "$railway" -eq 1 ]; then
        if [ "$name" = "staging" ] || [ "${APP_ENV:-}" = "staging" ]; then
            APP_ENV=staging
        elif [ -z "${APP_ENV:-}" ] || [ "$APP_ENV" = "local" ] || [ "$APP_ENV" = "development" ]; then
            APP_ENV=production
        fi
        APP_DEBUG=false
        case "${LOG_LEVEL:-}" in
            ""|debug) LOG_LEVEL=warning ;;
        esac
        export APP_ENV APP_DEBUG LOG_LEVEL
    fi
}
