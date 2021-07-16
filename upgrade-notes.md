# Notes regarding the Upgrade form 4 to 5
### Harpreet Singh <harpreet.singh.hora@logicielsolutions.co.in>
_______________________________________________
# Changes I did
    1. Made the users table admin_privlege column default 0
    2. Made the user_profile.position column nullable
    3. Made the user_profile.profile_pic column nullable
    4. Used the new dispatch method in prospects controller instead of execute command
    5. Entrust changes the config to have the role and user relations table updated.
# Backlogs
    1. A few Models are in Service directory. They should be in the Models Directory.
    2. Recurly error about Token already used; Need to check.
# Suggestions
    1. Need to able to set all the configs through .env files
        - Either make different .env for different environments
        - Such as local.env staging.env etc.
    2. Change the routes `As` and `Uses` to more laravel 5 compliant
        - Route::get('url', 'Controller@method')->name('route.name');
        - Routes should also be cached for better performance; this was not available in laravel 4
    3. Install itsgoingd/clockwork for profiling and debugging and finding bottlenecks in code and queries.
    4. Entrust plugin should go away isn't serving any purpose than to provide the tables and schema; see if avoidable