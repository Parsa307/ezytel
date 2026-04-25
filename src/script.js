document.addEventListener("DOMContentLoaded",()=>{
    // register channels actions
    document.querySelectorAll(".menu_channel_wrapper").forEach(element => {
        var chid = element.querySelector(".menu_channel_info p").innerText;
        element.setAttribute("chid",chid);
        element.addEventListener("click",()=>{
            document.querySelectorAll(".menu_channel_selected").forEach(elm => {
                elm.classList.remove("menu_channel_selected");
            });
            element.classList.add("menu_channel_selected");
            document.querySelector(".main_block").innerHTML = "";
            load_more(0,chid,null);
        });
    });
    // load channels updates
    let chnum = 0;
    document.querySelectorAll(".menu_channel_wrapper").forEach(element => {
        setTimeout(() => {
            var XHR = new XMLHttpRequest();
            XHR.open("GET","proxy.php?info="+element.getAttribute("chid"),true);
            XHR.send();
            XHR.onreadystatechange = function() {
                if (this.readyState == 4) {
                    if (this.status == 200){
                        let data = JSON.parse(XHR.responseText);
                        if ('avatar' in data && data['avatar'] != ''){
                            element.querySelector("img").src = data['avatar'];
                            element.querySelector("img").style.opacity = 1;
                        }
                        if ('name' in data){
                            element.querySelector(".menu_channel_info p").innerHTML = data['name'];
                        }
                        if ('desc' in data){
                            element.querySelector(".menu_channel_info span").innerHTML = data['desc'];
                        }
                        if ('newmsg' in data && 'datestr' in data){
                            element.querySelector(".menu_channel_stats").innerHTML = '<p>'+data['datestr']+'</p><span>'+data['newmsg']+'</span>';
                            if (data['newmsg'] == ''){
                                element.classList.add("menu_channel_allread");
                            }
                        }
                        if ('date' in data){
                            element.setAttribute("dateid",data['date']);
                            let move = null;
                            document.querySelectorAll(".menu_channel_wrapper").forEach(elem => {
                                if (move == null && parseInt(data['date']) >= parseInt(elem.getAttribute("dateid"))) move = elem;
                            });
                            if (move != null) document.querySelector(".menu_block").moveBefore(element,move);
                        }
                    }else{
                        element.querySelector(".menu_channel_info span").innerHTML = "NETWORK ERROR";
                    }
                }
            }
        }, chnum * 1000);
        chnum++;
    });
    // register search function
    document.querySelector(".tgme_header_search input").addEventListener("keyup",()=>{
        let search = document.querySelector(".tgme_header_search input").value.trim().toLowerCase();
        document.querySelectorAll(".menu_channel_wrapper").forEach(element => {
            if (search == '' || element.getAttribute("chid").includes(search) || element.querySelector(".menu_channel_info p").innerText.includes(search)){
                element.style.display = "flex";
            }else{
                element.style.display = "none";
            }
        });
    });
});
function load_more(next,chid,obj) {
    var XHR = new XMLHttpRequest();
    XHR.open("GET","proxy.php?next="+next+"&chid="+chid,true);
    document.querySelector(".page_progress_bar").style.display = "block";
    XHR.send();
    XHR.onreadystatechange = function() {
        if (this.readyState == 4) {
            document.querySelector(".page_progress_bar").style.display = "none";
            if (this.status == 200){
                if (obj != null) obj.parentNode.remove();
                let height = document.querySelector(".main_block").scrollHeight;
                document.querySelector(".main_block").innerHTML = XHR.responseText + document.querySelector(".main_block").innerHTML;
                if (next == 0){
                    document.querySelector(".main_block").scrollTo({top: document.querySelector(".main_block").scrollHeight});
                    if (document.querySelector('div[chid="'+chid+'"]')!=null){
                        document.querySelector('div[chid="'+chid+'"]').classList.add("menu_channel_allread");
                    }
                    document.querySelector(".menu_block").classList.add("menu_block_hide");
                    if (document.querySelector(".tgme_header")!=null){
                        document.querySelector(".tgme_header").addEventListener("click",()=>{
                            document.querySelector(".menu_block").classList.remove("menu_block_hide");
                            document.querySelectorAll(".menu_channel_selected").forEach(elm => {
                                elm.classList.remove("menu_channel_selected");
                            });
                            document.querySelector(".main_block").innerHTML = "";
                        });
                    }
                }else{
                    document.querySelector(".main_block").scrollTo({top: height});
                }
            }else{
                alert("something went wrong! try again!");
            }
        }
    }
}