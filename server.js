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
    "database": 'HSC_IPS',
    "port": 59877,
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
    //Need to add loop
    sql.connect(config, function (err) {
        if (err) console.log(err);
        var request = new sql.Request();

        var interval = 1000 * 60;
        var foo = setInterval (function () {
            console.log("Triggered")
            request.query('select * from temporary_park', function (err, recordset) {
                if (err) console.log(err)
                var data = recordset.recordsets[0];
                //console.log(data);
    
                var datecheck = new Date();
                console.log(datecheck +  "-");
                console.log("--------------------- ")
                data.forEach(function(item, index, arr) {
                    console.log("-----")
                    var parkOut = new Date(item.parkOut);
                    
                    parkOut.setTime(parkOut.getTime() + parkOut.getTimezoneOffset() * 60 * 1000 /* convert to UTC */ );
                    var strParkIn = item.parkIn.toISOString();
                    var strParkOut = item.parkOut.toISOString();
                    console.log(strParkIn + "-")
                    if (datecheck > parkOut) {
                        
                        request.query("INSERT INTO park_history(parkId, containerId, parkIn, parkOut, status, created_by) VALUES(" + item.parkId + "," + item.containerId + ", '" + strParkIn + "', '" + strParkOut + "', 0, 'admin')", function(err, result) {
                            if (err) {
                                console.log(err)
                            } else {
                                request.query("DELETE temporary_park WHERE id=" + item.id, function(err, result) {
                                    if (err) console.log(err)
                                    console.log(result);
                                })
                            }
                            console.log(result);
                            io.emit("parkFinished");
                        })
                    }
                })
            });
        }, interval);
    });
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