/**
 * Database handler functions
 */

var mysql = require('mysql');

defaults = {
    /** SETTING DEFAULT MODE HERE */
    'defaultConfig': 'local',

    'local': {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'nyc_restaurants'
    },
    'stg': {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'nyc_restaurants_stg'
    },
    'prod': {
        'host': 'localhost',
        'user': 'root',
        'password': 'root',
        'database': 'nyc_restaurants_prod'
    }
};

exports.getHandler = function(req) {
    var env = defaults.defaultConfig;

    var connection = mysql.createConnection({
        host     : defaults[env].host,
        user     : defaults[env].user,
        password : defaults[env].password,
        database : defaults[env].database
    });

    return connection;
}
