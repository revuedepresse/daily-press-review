'use strict';

angular.module('WTW', ['WTW.filters']).config(
    ['$routeProvider', function($routeProvider) {
        var templatesDir = '/sf2/bundles/wtwdashboard/wtw/partials/';

        $routeProvider.when('/github-repositories', {
            templateUrl: templatesDir + '/repositories.html',
            controller: ShowRepositoriesAction
        });
        $routeProvider.otherwise({redirectTo: '/github-repositories'});
    }]);
