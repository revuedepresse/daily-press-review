'use strict';

var weaverApp = angular.module('weaverApp', [
    'ngRoute',
    'statusController',
    'bookmarkController',
    'infinite-scroll',
    'jmdobry.angular-cache',
    'strictHttpsFilter'
]);

weaverApp.config(['$routeProvider', function($routeProvider) {
    $routeProvider.when('/:username', {
        templateUrl: '/mobile/app/partials/status.html',
        controller: 'ShowStatusesAction'
    });
    $routeProvider.when('/bookmarks/:username', {
        templateUrl: '/mobile/app/partials/bookmarks.html',
        controller: 'ShowBookmarksAction'
    });
    $routeProvider.otherwise({redirectTo: '/'});
}]);