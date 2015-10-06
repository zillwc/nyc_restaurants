var express = require('express');
var router = express.Router();
var Core = require('../models/Core.js');

/* GET home page. */
router.get('/', function(req, res, next) {
    res.sendfile('./views/index.html');
});

/** GET top 10 listing by type */
router.get('/top10/:type', function(req, res) {
	var type = req.params.type;
    var core = new Core(req);

    core.GetTop10({'type': type}, function(err, rows) {
        if (err)
            data = { "status": false, "message": err };
        else
            data = { "status": true, "data": rows };

        res.setHeader('Content-Type', 'application/json');
        res.send(JSON.stringify(data));
    });
});

/* BE WARY OF ROBOTS */
router.get('/robots.txt', function(req, res, next) {
    res.type('text/plain');
    res.send("User-agent: *\nDisallow: /");
});

module.exports = router;
