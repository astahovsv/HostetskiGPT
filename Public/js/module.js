
$(document).ready(function() {
	$('#conv-layout-main .gpt-message-toggle').click(function(e) {
		toggleGPTMessage($(this).attr('data-thread-id'), $(this).attr('data-message-index'));
		e.preventDefault();
	});
    $('#conv-layout-main .gpt-message-copy').click(function(e) {
		copyGPTMessage($(this).attr('data-thread-id'), $(this).attr('data-message-index'));
		e.preventDefault();
	});
});

// Togle gpt messages
function toggleGPTMessage(thread_id, message_index) {

	var trigger = $('#thread-'+thread_id+' .gpt-message-trigger-'+message_index+':first');
	var message = $('#thread-'+thread_id+' .gpt-message-'+message_index+':first');

	if (message.is(':visible')) {
		// Hide
		message.addClass('hidden');
		trigger.removeClass('selected');
	} else {
		// Show
		$('#thread-'+thread_id+' .gpt-message-text').addClass('hidden');
		$('#thread-'+thread_id+' .gpt-message-triggers a').removeClass('selected');
		message.removeClass('hidden');
		trigger.addClass('selected');
	}
}

// Copy gpt messages
function copyGPTMessage(thread_id, message_index) {
    const text = $(`#thread-${thread_id} .gpt-message-${message_index}`)[0].innerHTML;
    // navigator.clipboard.writeText(current_answer[0].innerHTML.replace(/<\/?.*?>/g, "").replaceAll("```", ""));
    navigator.clipboard.writeText(text);
    showFloatingAlert('success', gptassistantData.copiedToClipboard);
}

function hostetskigptInit() {
	$(document).ready(function(){
        // Add event listeners
        $(document).on("click", ".chatgpt-get", generateAnswer);
        $(document).on("click", ".gpt-nav-previous", previousAnswer);
        $(document).on("click", ".gpt-nav-next", nextAnswer);
        $(document).on("click", ".gpt-copy-icon", copyAnswer);

        if (document.location.pathname.startsWith("/conversation")) {
            const mailbox_id = $("body").attr("data-mailbox_id");

            const conversation_id = $("body").attr("data-conversation_id");
            $.ajax({
                url: '/hostetskigpt/answers?conversation=' + conversation_id,
                dataType: 'json',
                success: function (response, status) {
                    response.answers.forEach(function (item, index, array) {
                        item.answers.forEach(function (i, ind, arr) {
                            addAnswer(item.thread, i);
                        });
                        $(`#thread-${item.thread} .gpt-answer`).last().removeClass("hidden");
                        $(`#thread-${item.thread} .gpt-current-answer`).text($(`#thread-${item.thread} .gpt-answers div`).length);
                    });
                }
            });
        }
	});
}

function generateAnswer(e) {
    e.preventDefault();
    const text = $(e.target).closest(".thread").children(".thread-message").children(".thread-body").children(".thread-content").get(0).innerHTML.replace(/<\/?.*?>/g, "").trim();
    const query = encodeURIComponent(text);
    const thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    const mailbox_id = $("body").attr("data-mailbox_id");

    const customer_name = encodeURIComponent($(".customer-name").text());
    const customer_email = encodeURIComponent($(".customer-email").text().trim());
    const conversation_subject = encodeURIComponent($(".conv-subjtext span").text().trim());

    $(`#thread-${thread_id} .thread-info`).prepend("<img class=\"gpt-loader\" src=\"/modules/hostetskigpt/img/loading.gif\" alt=\"Test\">");

    fsAjax(`mailbox_id=${mailbox_id}&query=${query}&thread_id=${thread_id}&customer_name=${customer_name}&customer_email=${customer_email}&conversation_subject=${conversation_subject}`, '/hostetskigpt/generate', function (response) {
        $(`#thread-${thread_id} .gpt-answer`).last().addClass("hidden");
        addAnswer(thread_id, response.answer);
        $(`#thread-${thread_id} .gpt-answer`).last().removeClass("hidden");
        $(`#thread-${thread_id} .gpt-current-answer`).text($(`#thread-${thread_id} .gpt-answers div`).length);
        $(`#thread-${thread_id} .gpt-loader`).remove();
    }, true, function() {
        showFloatingAlert('error', Lang.get("messages.ajax_error"));
        $(`#thread-${thread_id} .gpt-loader`).remove();
    });
}

function addAnswer(thread_id, text) {
    if (!$(`#thread-${thread_id} .gpt`).length) {
        $(`#thread-${thread_id}`).prepend(`
        <div class="gpt">
            <strong>GPT Assistant:</strong>
            <br />
            <div class="gpt-answers-data">
                <div class="gpt-nav">
                    <svg style="margin-right: 2px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left gpt-nav-previous" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    <span class="gpt-current-answer">1</span>/<span class="gpt-max-answer">1</span>
                    <svg style="margin-left: 2px" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right gpt-nav-next" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                    </svg>
                </div>
                <div class="gpt-answers">

                </div>
                <span class="gpt-copy-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                        <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                        <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                    </svg>
                </span>
            </div>
        </div>`);
    }
    $(`#thread-${thread_id} .gpt-answers`).append(`
        <div class="gpt-answer hidden">${text}</div>
    `)
    $(`#thread-${thread_id} .gpt-max-answer`).text($(`#thread-${thread_id} .gpt-answers div`).length);
}

function previousAnswer(e) {
    const thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    const current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    const previous_answer = current_answer.prev();
    const current_answer_number = $(`#thread-${thread_id} .gpt-current-answer`);

    if (!previous_answer.length) return

    current_answer.addClass("hidden");
    previous_answer.removeClass("hidden");
    current_answer_number[0].innerHTML -= 1;
}

function nextAnswer(e) {
    const thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    const current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    const next_answer = current_answer.next();
    const current_answer_number = $(`#thread-${thread_id} .gpt-current-answer`);

    if (!next_answer.length) return

    current_answer.addClass("hidden");
    next_answer.removeClass("hidden");
    current_answer_number[0].innerHTML = current_answer_number[0].innerHTML - 0 + 1;
}

function copyAnswer(e) {
    const thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    const current_answer = $(`#thread-${thread_id} .gpt-answer`).not(".hidden");
    navigator.clipboard.writeText(current_answer[0].innerHTML.replace(/<\/?.*?>/g, "").replaceAll("```", ""));
    showFloatingAlert('success', gptassistantData.copiedToClipboard);
}
