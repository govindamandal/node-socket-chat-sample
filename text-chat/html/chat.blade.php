@extends('admin.admin_master')
@section('admin')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <div class="container-full">

        <!-- Main content -->
        <section class="content">
            <!-- Basic Forms -->
            <div class="box">
                <div class="box-header with-border">
                    <h2 class="box-title">Chat Inbox</h2>
                </div>
                <!-- /.box-header -->
                <div class="box-body" id="chat-content">
                    <div class="row justify-content-center h-100" style="height: 100vh !important; overflow: visible;">
                        <div class="col-md-4 col-xl-3 chat">
                            <div class="card mb-sm-3 mb-md-0 contacts_card">
                                <div class="card-header">
                                    <div class="input-group">
                                        <input type="text" placeholder="Search..." name="search"
                                            class="form-control search">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text search_btn"><i class="fa fa-search"></i></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body contacts_body">
                                    <ul class="contacts">
                                        @if ($chats)
                                            @php $key = 0 @endphp
                                            @foreach ($chats as $chat)
                                                <li @if ($key == 0) class="active" @endif
                                                    chat-user-id="{{ $chat['user']->id }}"
                                                    onclick="loadChat({{ $chat['user']->id }}, '{{ route('admin.ask.qustion.messages', $chat['user']->id) }}')">
                                                    <div class="d-flex bd-highlight">
                                                        <div class="img_cont">
                                                            <img src="{{ asset($chat['user']->profile_photo_path) }}"
                                                                class="rounded-circle user_img">
                                                            <span class="online_icon"></span>
                                                        </div>
                                                        <div class="user_info">
                                                            <span>{{ $chat['user']->name }}</span>
                                                            {{-- <p>Kalid is online</p> --}}
                                                        </div>
                                                    </div>
                                                </li>
                                                @php $key ++ @endphp
                                            @endforeach
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 col-xl-6 chat">
                            <div class="card">
                                <div class="card-header msg_head">
                                    <div class="d-flex bd-highlight" id="chat-head"></div>
                                </div>
                                <div class="card-body msg_card_body" id="chat-body">

                                </div>
                                <div class="card-footer" id="chat-foot">
                                    <div class="input-group">
                                        <div class="input-group-append">
                                            <span class="input-group-text attach_btn" onclick="sendAttachment()"><i class="fa fa-paperclip fa-2x"></i></span>
                                        </div>
                                        <textarea name="" class="form-control type_msg" placeholder="Type your message..." id="chatInput" style="text-decoration: none; outline: none; resize: none;"></textarea>
                                        <div class="input-group-append">
                                            <span class="input-group-text send_btn" id="send-button">
                                                    <i class="fa fa-location-arrow fa-2x"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->

        </section>
        <!-- /.content -->
    </div>
    <script src="https://cdn.socket.io/4.0.1/socket.io.min.js"
        integrity="sha384-LzhRnpGmQP+lOvWruF/lgkcqD+WDVt9fU3H4BWmwP5u5LTmkUGafMcpZKNObVMLU" crossorigin="anonymous">
    </script>
    <script src="{{ asset('backend/js/datetime.js') }}"></script>
    <script>
        let admin;
        let user;
        let asset = "{{ asset('/') }}";
        loadChat = (user_id, url) => {
            let users = document.querySelectorAll('[chat-user-id]');
            users.forEach(li => li.classList.remove('active'));
            document.querySelector('[chat-user-id="' + user_id + '"]').classList.add('active');

            xhr = new XMLHttpRequest();

            xhr.onreadystatechange = () => {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    let response = JSON.parse(xhr.response);
                    let chats = response.chats;
                    user = response.user;
                    admin = response.admin;

                    let head_html = '';
                    head_html += '<div class="img_cont">';
                    head_html += '    <img src="' + asset + user.profile_photo_path +
                        '" class="rounded-circle user_img">';
                    head_html += '    <span class="online_icon"></span>';
                    head_html += '</div>';
                    head_html += '<div class="user_info">';
                    head_html += '    <span>Chat with ' + user.name + '</span>';
                    head_html += '</div>';

                    document.getElementById('chat-head').innerHTML = head_html;



                    let body_html = '';

                    for (let c in chats) {
                        let chat = chats[c];
                        console.log(chat);
                        if (chat.sender == 'user') {
                            body_html += '<div class="d-flex justify-content-start mb-4">';
                            body_html += '    <div class="img_cont_msg">';
                            body_html += '        <img src="' + asset + user.profile_photo_path +
                                '" class="rounded-circle user_img_msg">';
                            body_html += '    </div>';
                            body_html += '    <div class="msg_cotainer">';

                            if (chat.attachment) {
                                body_html += '    <img src="' + chat.message + '" class="message-attachment" alt="attachment" />';
                            } else {
                                body_html += chat.message;
                            }

                            let message_date = new Date(chat.datetime);
                            let datetime = chat.datetime;

                            if (isToday(message_date)) {
                                datetime = formatTime(message_date) + ', Today';
                            } else if (isYesterday(message_date)) {
                                datetime = formatTime(message_date) + ', Yesterday';
                            } else {
                                datetime = formatDateTime(message_date);
                            }

                            body_html += '        <span class="msg_time">' + datetime + '</span>';
                            body_html += '    </div>';
                            body_html += '</div> ';
                        } else {
                            body_html += '<div class="d-flex justify-content-end mb-4">';
                            body_html += '    <div class="msg_cotainer_send">';

                            if (chat.attachment) {
                                body_html += '    <img src="' + chat.message + '" class="message-attachment" alt="attachment" />';
                            } else {
                                body_html += chat.message;
                            }

                            let message_date = new Date(chat.datetime);
                            let datetime = chat.datetime;

                            if (isToday(message_date)) {
                                datetime = formatTime(message_date) + ', Today';
                            } else if (isYesterday(message_date)) {
                                datetime = formatTime(message_date) + ', Yesterday';
                            } else {
                                datetime = formatDateTime(message_date);
                            }

                            body_html += '        <span class="msg_time_send">' + datetime + '</span>';
                            body_html += '    </div>';
                            body_html += '    <div class="img_cont_msg">';
                            body_html += '        <img src="' + asset + admin.profile_photo_path +
                                '" class="rounded-circle user_img_msg">';
                            body_html += '    </div>';
                            body_html += '</div>';
                        }
                    }

                    document.getElementById('chat-body').innerHTML = body_html;
                    document.getElementById('chat-body').scrollTop = 10000;
                }
            };

            xhr.open("GET", url);
            xhr.send();
        };

        (() => {
            let users = document.querySelectorAll('[chat-user-id]');
            if (users.length) {
                users[0].click();
            }

            let ip_address = "{{ env('APP_HOST') }}";
            let socket_port = "{{ env('APP_PORT') }}";
            let socket = io(ip_address + ':' + socket_port);
            let chatInput = document.getElementById('chatInput');

            chatInput.addEventListener('keypress', function(e) {

                let message = chatInput.value;

                if (message.trim().length) {

                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage(message)
                        return false;
                    }
                }
            });

            sendMessage = (message, attachment = 0) => {
                let userel = document.querySelector('[chat-user-id].active');
                let messageObj = {
                    message: message,
                    attachment: attachment,
                    admin_id: {{ $admin->id }},
                    user_id: parseInt(userel.getAttribute('chat-user-id')),
                    sender: 'admin'
                };

                socket.emit('sendChatToServer', messageObj);
                chatInput.value = '';

                let chat_body = document.createElement('div');
                chat_body.setAttribute('class', 'd-flex justify-content-end mb-4');

                let body_html = '';

                body_html += ' <div class="msg_cotainer_send">';

                if (attachment) {
                    body_html += '<img src="' + messageObj.message + '" class="message-attachment" alt="attachment"/>';
                } else {
                    body_html += messageObj.message;
                }

                let message_date = new Date();
                let datetime = formatTime(message_date) + ', Today';

                body_html += '     <span class="msg_time_send">' + datetime + '</span>';
                body_html += '    </div>';
                body_html += ' <div class="img_cont_msg">';
                body_html += '     <img src="' + asset + admin.profile_photo_path + '" class="rounded-circle user_img_msg">';
                body_html += ' </div>';

                chat_body.innerHTML = body_html;

                document.getElementById('chat-body').appendChild(chat_body);
                document.getElementById('chat-body').scrollTop = 10000;
            }

            socket.on('sendChatToClient', (msgArr) => {
              for (let c in msgArr) {
                let chat = msgArr[c];
                if (chat.user_id == user.id && chat.admin_id == admin.id) {
                  let chat_body = document.createElement('div');
                  chat_body.setAttribute('class', 'd-flex justify-content-start mb-4');

                  let body_html = '';

                  body_html += '    <div class="img_cont_msg">';
                  body_html += '        <img src="' + asset + user.profile_photo_path +
                      '" class="rounded-circle user_img_msg">';
                  body_html += '    </div>';
                  body_html += '    <div class="msg_cotainer">';
                  body_html += chat.message

                  let message_date = new Date(chat.datetime);
                  let datetime = chat.datetime;

                  if (isToday(message_date)) {
                      datetime = formatTime(message_date) + ', Today';
                  } else if (isYesterday(message_date)) {
                      datetime = formatTime(message_date) + ', Yesterday';
                  } else {
                      datetime = formatDateTime(message_date);
                  }

                  body_html += '        <span class="msg_time">' + datetime + '</span>';
                  body_html += '    </div>';

                  chat_body.innerHTML = body_html;

                  document.getElementById('chat-body').appendChild(chat_body);
                  document.getElementById('chat-body').scrollTop = 10000;
                }
              }
            });
        })()

        let send_button = document.getElementById('send-button');
        send_button.addEventListener('click', () => {
            let chatInput = document.getElementById('chatInput');

            if (chatInput.value.trim() != '') {
                sendMessage(chatInput.value);
            }
        });

        sendAttachment = () => {
            let chat_foot = document.querySelector('#chat-foot');
            if (document.querySelector('#attachment-file')) {
                chat_foot.removeChild(document.querySelector('#attachment-file'));
            }

            let input = document.createElement('input');
            input.type = 'file';
            input.id = 'attachment-file';
            input.classList.add('d-none');
            input.accept = 'image/*';
            chat_foot.appendChild(input);
            document.querySelector('#attachment-file').click();
            document.querySelector('#attachment-file').addEventListener('change', (event) => {
                const file = event.target.files[0];
                const reader = new FileReader();
                reader.onloadend = () => {
                    sendMessage(reader.result, 1);
                };
                reader.readAsDataURL(file);
            })
        };

    </script>
@endsection
