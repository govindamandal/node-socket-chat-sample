const express = require('express')
const app = express()
const https = require('https')
const cors = require('cors')
const fs = require('fs')
app.use(cors)
app.use(function(req, res, next) {
   res.header("Access-Control-Allow-Origin", "*");
   res.header("Access-Control-Allow-Credentials", true);
   res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
   next();
});
const options = {
  key: fs.readFileSync('ssl/privkey.pem'),
  cert: fs.readFileSync('ssl/fullchain.pem')
};
// const server = require('http').createServer(app)
// creating https server
const server = https.createServer(options, app);

require('dotenv').config();
const mysql = require('mysql');
const port = process.env.BROADCAST_DRIVER;
const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_DATABASE
});

const io = require('socket.io')(server, {
    cors: {
      origin: "https://jobportal.itexpertiseindia.com",
      methods: ["GET", "POST"],
      allowedHeaders: [{"Access-Control-Allow-Credentials": true}],
      credentials: true
    },
    // allowEIO3: true // use this if client side has lower version
});

// app.get('/', (req, res) => {
//   res.send({
//     name: 'Govinda',
//     email: 'govinda@gmail.com'
//   })
// })

io.on('connection', (socket) => {
    socket.on('sendChatToServer', (msgObj) => {
        db.query("INSERT INTO jobs_chat SET employer_id=" + msgObj.employer_id + ", employee_id =" + msgObj.employee_id + ", message=" + db.escape(msgObj.message) + ", sender='" + msgObj.sender + "', attachment=" + msgObj.attachment + ", datetime= NOW()", (error, result) => {
            if (error) {
                throw error
            } else {
                let sql = "SELECT c.id, c.employer_id, c.employee_id, c.message, c.attachment, c.sender, c.datetime, er.ju_firstnmame AS employer_name , er.profile_pic AS empoyer_profile_pic, ee.ju_firstnmame AS employee_name, er.profile_pic AS empoyee_profile_pic FROM jobs_chat c LEFT JOIN jobs_users er ON (c.employer_id = er.ju_id) LEFT JOIN jobs_users ee ON (c.employee_id = ee.ju_id) WHERE c.id = " + result.insertId;

                db.query(sql, (error, result) => {
                    if (!error) {
                        socket.broadcast.emit('sendChatToClient', result);
                    }
                });
            }
        });
    });
    // socket.on('startCalling', (data) => {
    //   if (data.employer_id && data.employee_id) {
    //     db.query("INSERT INTO job_chat_call SET employer_id=" + data.employer_id + ", employee_id=" + data.employee_id + ", type=" + data.type + ", status='pending', call_by='" + data.call_by + "'", (error, result) => {
    //       if (error) {
    //         throw error
    //       } else {
    //         socket.broadcast.emit('sendCallToReceive', data)
    //       }
    //     })
    //   }
    // })

    socket.on('endCall', (data) => {
      console.log('end-call');
      db.query("UPDATE job_chat_call SET status='end' WHERE employee_id = " + data.employee_id + " AND employee_id = " + data.employee_id)
      socket.broadcast.emit('callEnded', data)
    })

    socket.on('disconnect', (socket) => {
        console.log('Disconnect');
    });
});

server.listen(3001, () => {
    console.log(port);
    console.log('Server is running');
});
