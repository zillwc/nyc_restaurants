var db = require('./Database.js');

var Core = function(req) {
    this.req = req;
    this.conn = db.getHandler(this.req);
    this.conn.connect();
}

// Returns the top 10 restaurants [TODO: filter by params.type]
Core.prototype.GetTop10 = function(params, callback) {
    query = "SELECT * FROM top_10_restaurants t LEFT JOIN restaurant r ON r.id=t.restaurant_id ORDER BY t.insert_timestamp DESC LIMIT 10";
    result = this.conn.query(query, function(err, rows) {
        if (err)
            callback(err);
        else
            callback(null, rows);
    });

    this.conn.end();
}

module.exports = Core;