<?php

/** @var yii\web\View $this */

use chillerlan\QRCode\QRCode;
use newerton\fancybox\FancyBox;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'QRcodest';
$this->registerCss('
    .input-group label { margin: 7px 10px 0 }
    .field-link-url_full { position: relative; width: 88%; }
    #urlok { display:flex; float:right; padding: 3px 8px; }
    button[disabled] { opacity: 0.4!important }
    img.qrcode { max-width: 200px }
    h4 u { color: #888!important; cursor: pointer; font-size: 20px; }
    #links a[data-fancybox] { margin-right: 10px }
    #links img { max-height: 30px }
    u[data-href] { color: #900; cursor: pointer; float: right; }
');
?>
<div class="site-index d-flex flex-column justify-content-center">

    <div class="p-2 mb-2 text-center bg-body-tertiary rounded-3" style="max-width: 600px; margin: 0 auto; ">
        <h1 class="display-5 mb-4 fw-bold">Сервис коротких ссылок</h1>
        <?php
            $form = ActiveForm::begin([
                'action' => Url::to(['create-link']),
                'enableAjaxValidation' => true,
                // 'enableClientValidation' => false,
                'id' => 'create-link',
                'method' => 'POST',
            ]);

            echo 
                Html::button('Ok?', ['onclick' => 'window.urlcheck()', 'id' => 'urlok']),
                $form->field($model, 'url_full', [
                    'inputOptions' => ['class' => 'form-control', 'autofocus' => true],
                    'options' => ['class' => 'input-group has-validation mb-3'
                ],
                ])->textInput([
                    'placeholder' => 'https://...',
                    'value' => 'https://ya.ru/',
                    'type' => 'url',
                ])->label('Ссылка'),
                Html::tag('div', '', ['id' => 'check-result']),
                Html::submitButton('Сгенерировать короткую ссылку',
                    ['class' => 'btn btn-primary px-2 ms-auto', 'disabled' => 'disabled', 'id' => 'save']
                );

            ActiveForm::end();
        ?><br>
        <h4>Предыдущие ссылки</h4>
        <div id="links">
            <?php
                foreach(is_array($links) ? $links : [$links] as $link) {
                    echo Html::tag('div',
                        Html::a(Html::img($link->qrcode), $link->qrcode, ['data-fancybox' => $link->qrcode, 'rel' => 'fancybox']) . 
                        Html::a(
                            substr($link->url_full, 0, 44) . ((strlen($link->url_full) > 44) ? '...' : ''),
                            Url::to(['go/'.$link->url_short, 't' => time()]), ['title' => $link->url_full, 'target' => '_blank']
                        ) . date(' - H:i', strtotime($link->created_at)) . ', кликов - ' . $link->clicks
                    );
                }
                if (!empty($links)) {
                    echo '<div><u data-href="refresh">заново</u><i>QR-код можно увеличить</i></div>';
                }
            ?>
        </div>
    </div> 
</div>
<?php
$this->registerJs('
    let $form = $("form#create-link:first");

    $form.find("input").keydown(function(event) {
        if (event.which == 13) { // ENTER
            $("#urlok").click();
            return false;
        }
    });

    $form.off("submit").on("submit", function(event) {
        event.preventDefault();

        var formData = new FormData(this);
        formData.append("save", "save");
        $.ajax({
            url: "' . Url::to(['create-link']). '",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log(response.url_short);
                $("#links").prepend(response.html);
                $("#links a[data-fancybox]").fancybox();
                $("#save").prop("disabled", true);
                $("#link-url_full").val("");
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                $("#save").prop("disabled", true);
                $("#check-result").html("<p>Ошибка: " + JSON.parse(xhr.responseText).message + "</p>");
            }
        });

        return false;
    });

    window.urlcheck = async function checkUrlIsWorking() {
        let url = $("#link-url_full").val();
        if (url.length < 7 /* http:// */) return false;
        try {
            new URL(url);
            window.urltocheck = url;
            const response = await fetch(window.urltocheck, {
                method: "HEAD",
                mode: "no-cors"
            });
            $("#save").prop("disabled", false);
            $("#check-result").text(window.urltocheck + " is ok.");
            return response.ok; 
        } catch (error) {
            $("#check-result").text(window.urltocheck + " URL недоступен / неизвестная ошибка");
            // alert("Данный URL недоступен");
            return false;
        }
    }

    $("#urlok").click(function() {
        console.log("checking...");
        window.urlcheck;
    });

    $("u[data-href]").click(function(event) {
        if (confirm("Перезапустить миграции?")) {
            window.location.href = event.target.dataset.href;
        }
    });
');
echo FancyBox::widget([
    'target' => 'a[rel=fancybox]',
    'config' => [
        'openEffect' => 'elastic',
        'closeEffect' => 'elastic',
        'helpers' => [
            'title' => ['type' => 'float'],
            'buttons' => [],
        ],
    ]
]);
