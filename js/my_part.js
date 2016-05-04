/**
 * Created by moi on 23/04/2016.
 */
$(function () {
    open = false;
    $("#result").hide();
    $("#loading").hide();
    $("#error").hide();
    var url_var = getUrlVars();
    if("pseudo" in url_var && "region" in url_var)
    {
        $("#summoner_name").val(url_var['pseudo']);
        $("#region").val(url_var["region"]);
        console.log(url_var["pseudo"] + " " + url_var["region"]);
        request_display(url_var["pseudo"], url_var["region"]);
    }

    $("#go").click(function(){
        // Animation
        if($("#summoner_name").val() != "" && $("#region").val() != "")
            request_display($("#summoner_name").val(), $("#region").val());
    });
});

function request_display(pseudo, region)
{
    $("#result").hide();
    $("#error").hide();
    $("#loading").fadeIn();
    if (open) {
        console.log("open -> close");
        $("#in_stats").remove();
        $("#in_champ").remove();
        $("#stats").append("<div id='in_stats'></div>");
        $("#champions").append("<div id='in_champ'></div>");
        open = false;
    } else {
        console.log("close");
    }
    var url = 'server/app.php?pseudo=' + encodeURIComponent(pseudo) + "&region=" + encodeURIComponent(region.toLowerCase());
    $.getJSON(url, function(data) {
        if(!("error" in data)) {
            // Display stats
            // Total points
            var value_stats;
            if (data.stats.tot_points > 1000)
                value_stats = generatePanel("Total Points", data.stats.tot_points / 1000 + " k ", "points");
            else
                value_stats = generatePanel("Total Points", data.stats.tot_points, "points");

            // Title page
            document.title = pseudo + "'s champion pull";

            // Buttons share
            var url_actual;
            console.log(window.location.href);
            if (window.location.href.slice(window.location.href.length - 1) == "/")
                url_actual = window.location.href + "index.html?pseudo=" + pseudo + "&region=" + region;
            else if (window.location.href.slice(window.location.href.length - 10) == "index.html")
                url_actual = window.location.href + "?pseudo=" + pseudo + "&region=" + region;
            else {
                var param_url = getUrlVars();
                if (param_url["pseudo"] == pseudo && param_url["region"] == region)
                    url_actual = window.location.href;
            }
            var url_twitter = "https://twitter.com/intent/tweet?original_referer=https%3A%2F%2Fabout.twitter.com%2Ffr%2Fresources%2Fbuttons&ref_src=twsrc%5Etfw&text=" + encodeURIComponent("Thats my champion pull bro !") + "&tw_p=tweetbutton&url=" + encodeURIComponent(url_actual);
            var url_fb = "https://www.facebook.com/sharer/sharer.php?app_id=113869198637480&sdk=joey&u=" + encodeURIComponent(url_actual) + "&display=popup&ref=plugin&src=share_button";
            $("#twitter_button_share").attr("href", url_twitter);
            $("#fb_button_share").attr("href", url_fb);

            // Average difficulty
            console.log(data.stats.somme_pond_diff);
            value_stats = value_stats +
                generatePanel("Average of the champions played difficulty",
                    Math.round((data.stats.somme_pond_diff / (data.stats.tot_points * 10)) * 1000) / 100 + "/10",
                    progressBar(Math.round((data.stats.somme_pond_diff / (data.stats.tot_points * 10)) * 10000) / 100));

            // Number of played champion
            value_stats = value_stats +
                generatePanel("Number of champions played", data.stats.compte, "champions played.");

            // Average attack
            value_stats = value_stats +
                generatePanel("<span class='glyphicon glyphicon-scissors'></span> Average of the champions played AD",
                    Math.round((data.stats.somme_attack / (data.stats.tot_points * 10)) * 1000) / 100 + "/10",
                    progressBar(Math.round((data.stats.somme_attack / (data.stats.tot_points * 10)) * 10000) / 100));

            // Average defense
            value_stats = value_stats +
                generatePanel("<span class='glyphicon glyphicon-heart-empty'></span> Average of the champions played Tank",
                    Math.round((data.stats.somme_defense / (data.stats.tot_points * 10)) * 1000) / 10 + "/10",
                    progressBar(Math.round((data.stats.somme_defense / (data.stats.tot_points * 10)) * 10000) / 100));

            // Average magic
            value_stats = value_stats +
                generatePanel("<span class='glyphicon glyphicon-hourglass'></span> Average of the champions played AP",
                    Math.round((data.stats.somme_magic / (data.stats.tot_points * 10)) * 1000) / 100 + "/10",
                    progressBar(Math.round((data.stats.somme_magic / (data.stats.tot_points * 10)) * 10000) / 100));

            $("#in_stats").append(value_stats);

            // Display champions
            var value_champ;
            var last_level = -1;
            for (var i = 0; i < data.champions.length; i++) {
                if (last_level == -1) {
                    last_level = data.champions[i].championLevel;
                    value_champ = "<h4>Level " + last_level + " (" + data.stats.count_level[last_level] + ")</h4><div class='champ_level' id='champ_level" + last_level + "'>";
                }
                else if (last_level != data.champions[i].championLevel) {
                    last_level = data.champions[i].championLevel;
                    value_champ = value_champ + "</div><hr><h4>Level " + last_level
                        + " (" + data.stats.count_level[last_level] + ")</h4><div class='champ_level' id='champ_level"
                        + last_level + "'>";

                }
                value_champ = value_champ +
                    "<div class='well'><img class='icone_champ' style='background: url(" + "server/data/images/sprite/"
                    + data.champions[i].champion.image.sprite + ") -" + data.champions[i].champion.image.x + "px -"
                    + data.champions[i].champion.image.y + "px;'/><div class='champ_data'>";
                if (data.champions[i].championPoints >= 1000)
                    value_champ = value_champ + "<span><strong>" + data.champions[i].championPoints / 1000 + "k points</strong></span>";
                else
                    value_champ = value_champ + "<span><strong>" + data.champions[i].championPoints + " points</strong></span>";
                value_champ = value_champ + " => " + Math.round(data.champions[i].championPoints * 10000 / data.stats.tot_points) / 100 + "% of the total points<br/>";
                value_champ = value_champ + "<span><strong>" + data.champions[i].highestGrade + " is the highest Grade</strong></span>";
                value_champ = value_champ + "</div></div>";
            }
            value_champ = value_champ + "</div>";
            $("#in_champ").append(value_champ);
            open = true;
            $("#result").fadeIn(1200, 'swing');
        }
        else
        {
            $("#error").html("<strong>Error : </strong>" + data.error);
            $("#error").fadeIn(600, 'swing');
        }
    })
        .fail(function(){
            $("#error").html("<strong>Error : </strong> Internal server")
            $("#error").fadeIn(600, 'swing');
        })
        .always(function(){
            $("#loading").hide();
        });
}

function generatePanel(title, number, data)
{
    return '<div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">' + title
        + '</h3></div><div class="panel-body"><h4>' + number + '</h4>' + data + '</div></div>';
}

function progressBar(percent)
{
    return '<div class="progress">' +
        '<div class="progress-bar progress-bar-striped" role="progressbar" aria-valuenow="' + percent + '" aria-valuemin="0" aria-valuemax="100" style="width:'
        + percent + '%;"> ' + percent + '%</div></div>';
}

function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}