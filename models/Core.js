var db = require('./Database.js');

var Core = function(req) {
    this.req = req;
    this.conn = db.getHandler(this.req);
    this.conn.connect();
}

Core.prototype.GetTop10 = function(params, callback) {

    query = "";
    result = this.conn.query(query, function(err, rows) {
        if (err)
            callback(err);
        else
            callback(null, rows);
    });

    this.conn.end();
}

module.exports = Core;