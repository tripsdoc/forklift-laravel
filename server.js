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

var app = require('http').createServer(handler);
var io = require('socket.io')(app);

var ioRedis = require('ioredis');
var redis = new ioRedis(REDIS);

app.listen(SOCKET_PORT, function() {
    console.log(new Date + ' - Server is running on port ' + SOCKET_PORT + ' and listening Redis on port ' + REDIS.port + '!');

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

io.on('connection', function(socket) {
    console.log('A client connected');
})

redis.subscribe('__keyevent*:set*', function(err, count) {
    console.log('Subscribed');
})

redis.on('message', function(subscribed, channel, data) {
    //data = JSON.parse(data);
    console.log(channel);
    console.log(new Date);
    console.log(data);
    //io.emit(channel + ':' + data.event, data.data);
})

redis.monitor(function (err, monitor) {
    monitor.on("monitor", function(time, args, source, database) {
        console.log(time);
        console.log(args);
        console.log(source);
        console.log(database);
    })
})
