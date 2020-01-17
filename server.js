const SOCKET_PORT = 9131;
const REDIS = {
    "host" : "192.168.14.88",
    "port" : "6379"
}
var sql = require("mssql");
var config = {
    "user": 'Angga',
    "password": 'P@ssw0rd',
    "server": '192.168.16.3',
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

io.on('connection', function(socket) {
    console.log("Client Connected")
})

app.listen(SOCKET_PORT, function() {
    sql.connect(config, function (err) {
        console.log("Triggered SQL")
        if (err) console.log(err);
        var request = new sql.Request();
        var interval = 1000 * 60 * 60;
        var intervalDel = 1000 * 60 * 60 * 24 * 7
        var intervalTemporary = 1000 * 60 * 60 * 2
        var foo = setInterval (function () {
            var current = utcToLocal(new Date())
            request.query('SELECT * FROM HSC_OngoingPark', function (err, recordset) {
                if (err) console.log(err)
                var data = recordset.recordsets[0];
                data.forEach(function(item, index, arr) {
                    var dateSql = new Date(item.updatedDt + "")
                    var dateCheck = new Date(dateSql.getTime() + intervalTemporary)
                    var itemdate = item.updatedDt
                    if (current > dateCheck) {
                        request.query("DELETE HSC_OngoingPark WHERE ParkingID = '" + item.ParkingID + "'", function (err, recordset) {
                            if (err) console.log(err)
                            console.log("Temporary Park is Deleted")
                            io.emit("onParkFinished","Park Update")
                        })
                        
                        request.query("INSERT INTO HSC_ParkHistory (SetDt, UnSetDt, ParkingLot, Dummy, createdBy, createdDt) VALUES(" + "convert(datetime,'" + convertDatabase(itemdate) + "',20)" + "," + "convert(datetime,'" + convertDatabase(current) + "',20)" + ",'" + item.ParkingLot + "','" + item.Dummy + "','Admin'," + "convert(datetime,'" + convertDatabase(current) + "',20)" + ")", function (err, recordset) {
                            if (err) console.log(err)
                            console.log("Temporary Park is Now in History")
                        })
                    }
                })
            });
            request.query('SELECT * FROM HSC_ParkHistory', function (err, recordset) {
                if (err) console.log(err)
                var data = recordset.recordsets[0];
                data.forEach(function(item, index, arr) {
                    var dateSql = new Date(item.createdDt)
                    var dateCheck = new Date(dateSql.getTime() + intervalDel)
                    if (current > dateCheck) {
                        request.query("DELETE HSC_ParkHistory WHERE HistoryID = '" + item.HistoryID + "'", function (err, recordset) {
                            if (err) console.log(err)
                            console.log("Park History is Deleted")
                        })
                    }
                })
            })
        }, interval);
    });
});

data.listen(9132, function () {
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

var getmember = async(data, params) => {
    let val = await redis.zrange(data, 0, -1)
    return val
};

var getzrange = async(data) => {
    let val = await redis.zrevrangebyscore(data, '+inf', '-inf', 'LIMIT', 0, 1)
    return val
};

var getkeys = async(data) => {
    let val = await redis.keys(data + '*')
    val.sort()
    var lastitem = val.pop();
    return lastitem
};

var getarraykeys = async(data) => {
    let val = await redis.keys('*' + data + '*')
    val.sort()
    return val
}

var getdata = async(data) => {
    let val = await redis.get(data)
    return val
};
data.get('/tag=:id', awaitHandlerFactory( async(req, res, next) => {
    var selectedkeys = await getzrange(req.params.id)
    console.log(selectedkeys)
    var obj = JSON.parse(selectedkeys)
    //var data = await getdata(selectedkeys)
    var datas = {
        code: (data != null) ? 0 : 13,
        command: "http://192.168.14.70:9131/tag=" + req.params.id,
        message: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        responseTS: Date.now(),
        status: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        tags: [obj],
        version: 1
    }
    res.json(datas)
}))

data.get('/debug/tag=:id', awaitHandlerFactory( async(req, res, next) => {
    var selectedkeys = await getkeys(req.params.id)
    var data = await getdata(selectedkeys)
    var datas = {
        code: (data != null) ? 0 : 13,
        command: "http://192.168.14.70:9131/tag=" + req.params.id,
        message: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        responseTS: Date.now(),
        status: (data != null) ? "Tag Position" : "Unknown Tag Listed",
        tags: JSON.parse(data),
        version: 1
    }
    res.json(datas)
}))

data.get('/tagsdata=:id', awaitHandlerFactory(async(req, res, next) => {
    redis.keys('*' + req.params.id + '*', function(err, result) {
        res.send(result)
    })
}))

data.get('/keys=:id', awaitHandlerFactory(async(req,res,next) => {
    var selectedkeys = await getdata(req.params.id)
    res.send(selectedkeys)
}))

data.get('/debug/sort=:id', awaitHandlerFactory(async(req,res, next) => {
    var selectedkeys = await getkeys(req.params.id)
    var data = await getmember(req.params.id)
    //console.log("val", selectedkeys)
    var datas = {
        selected: selectedkeys,
        array: await getarraykeys(req.params.id),
        data: data
    }
    res.json(datas)
}))

//Listen to redis data change
redis.monitor(function (err, monitor) {
    monitor.on("monitor", function(time, args, source, database) {
        if(args[0] == "SET" || args[0] == "set") {
            var fullkey = args[1]
            var splitkey = fullkey.split('_')
			console.log(splitkey[0])
            io.emit(splitkey[0],JSON.parse(args[2]))
        }
    })
})

redis.subscribe('update-park', function(err, count) {
    console.log(count)
})

redis.on('message', function(channel, message) { 
    if (channel == "update-park") {
        io.emit("onParkFinished", "Park Updated")
        var data = message
        var splitdata = message.split(',')
        var data = '{"type":' + splitdata[0] + ',"data":' + splitdata[2] + "}"
        io.emit("park" + splitdata[1], JSON.parse(data))
    }
    
    
    console.log('Message Recieved: ' + channel + "with :" + message);
});

var utcToLocal = function (date) {
    return new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate(),  date.getHours(), date.getMinutes(), date.getSeconds()));
}

var localToUTC = function(date){
    return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(),  date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds());
}