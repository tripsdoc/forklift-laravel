const SOCKET_PORT = 9000;
const REDIS = {
    "host" : "192.168.14.88",
    "port" : "6379"
}

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
