const SOCKET_PORT = 9000;
const REDIS = {
    "host" : "192.168.14.88",
    "port" : "6379"
}
var sql = require("mssql");
var config = {
    "user": '',
    "password": '',
    "server": '',
    "database": 'HSC2017Test_V2',
    "port": 1433,
    "dialect": "mssql",
    "dialectOptions": {
        "instanceName": "SQLEXPRESS"
    }
};

function handler(request, response) {
    response.writeHead(200);
    response.end('');
}

var async = require('async');
var express = require('express');
var data = express();
var app = require('http').createServer(handler);
var io = require('socket.io')(app);

var ioRedis = require('ioredis');
var redis = new ioRedis(REDIS);

app.listen(SOCKET_PORT, function() {
    //Check function
    
    //Check if new month

    var interval = 1000 * 60;
    var foo = setInterval(function () {
        console.log("Triggered")
    }, interval)
    //Select last month data
    /* sql.connect(config, function (err) {
        if (err) console.log(err);
        var request = new sql.Request();

        var interval = 1000 * 60 * 60 * 24;
        var foo = setInterval (function () {
            console.log("Triggered")
            request.query('SELECT * FROM HSC_ParkHistory WHERE createdDt BETWEEN([LASTMONTHFIRST], [LASTMONTHEND])', function (err, recordset) {
                if (err) console.log(err)
                
                //Iterate History
                TODO
                var data = recordset.recordsets[0];
                data.forEach(function(item, index, arr) {
                    request.query("SELECT * FROM Onee WHERE Dummy = '" + item.Dummy + "'", function (err, recordset) {
                        //Save to file
                    })
                })
            });
        }, interval);
    }); */
});

data.listen(8081, function () {
})

const awaitHandlerFactory = (middleware) => {
    return async (req, res, next) => {
      try {
        await middleware(req, res, next)
      } catch (err) {
        next(err)
      }
    }
}

var getzrange = async(data) => {
    let val = await redis.zrevrangebyscore(data, '+inf', '-inf', 'LIMIT', 0, 1)
    return val
};


data.get('/tag=:id', awaitHandlerFactory( async(req, res, next) => {
    var selectedkeys = await getzrange(req.params.id)
    console.log(selectedkeys)
    var obj = JSON.parse(selectedkeys)
    //var data = await getdata(selectedkeys)
    var datas = {
        code: (data != null) ? 0 : 13,
        command: "http://192.168.14.147:8081/tag=" + req.params.id,
        message: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        responseTS: Date.now(),
        status: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        tags: [obj],
        version: 1
    }
    res.json(datas)
}))

io.on('connection', function(socket) {
    
})

//Listen to redis data change
redis.monitor(function (err, monitor) {
    monitor.on("monitor", function(time, args, source, database) {
        if(args[0] == "SET" || args[0] == "set") {
            var fullkey = args[1]
            var splitkey = fullkey.split('_')
            io.emit(splitkey[0],JSON.parse(args[2]))
        }
    })
})