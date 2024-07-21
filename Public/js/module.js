
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
    navigator.clipboard.writeText(text.replace(/<\/?.*?>/g, "").replaceAll("```", ""));
    showFloatingAlert('success', gptassistantData.copiedToClipboard);
}

function hostetskigptInit() {
	$(document).ready(function(){
        $(document).on("click", ".chatgpt-get", generateAnswer);
	});
}

function generateAnswer(e) {
    e.preventDefault();
    
    const text = $(e.target).closest(".thread").children(".thread-message").children(".thread-body").children(".thread-content").get(0).innerHTML.replace(/<\/?.*?>/g, "").trim();
    const query = encodeURIComponent(text);
    const thread_id = $(e.target).closest(".thread").attr("data-thread_id");
    const mailbox_id = $("body").attr("data-mailbox_id");

    $(`#thread-${thread_id} .thread-info`).prepend("<img class=\"gpt-loader\" src=\"/modules/hostetskigpt/img/loading.gif\" alt=\"Test\">");

    fsAjax(
        {
            mailbox_id: mailbox_id,
            thread_id: thread_id,
            query: query
        },
        '/hostetskigpt/generate',
        function (response) {
            window.location.href = '';
        }, 
        true, 
        function() {
            showFloatingAlert('error', Lang.get("messages.ajax_error"));
            $(`#thread-${thread_id} .gpt-loader`).remove();
        }
    );
}
