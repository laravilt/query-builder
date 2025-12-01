/**
 * QueryBuilder Plugin for Vue.js
 *
 * This plugin can be registered in your main Laravilt application.
 *
 * Example usage in app.ts:
 *
 * import QueryBuilderPlugin from '@/plugins/query-builder';
 *
 * app.use(QueryBuilderPlugin, {
 *     // Plugin options
 * });
 */

export default {
    install(app, options = {}) {
        // Plugin installation logic
        console.log('QueryBuilder plugin installed', options);

        // Register global components
        // app.component('QueryBuilderComponent', ComponentName);

        // Provide global properties
        // app.config.globalProperties.$query-builder = {};

        // Add global methods
        // app.mixin({});
    }
};
