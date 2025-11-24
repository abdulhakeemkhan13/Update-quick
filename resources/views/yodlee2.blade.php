<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Yodlee FastLink Laravel Demo</title>
    <script src="https://cdn.yodlee.com/fastlink/v3/initialize.js"></script>
</head>

<body>
    <h2>Yodlee FastLink Demo (Laravel Backend)</h2>
    <div id="container-fastlink" style="margin-top:20px;">
        <button id="btn-fastlink">Link Account</button>
    </div>

    <script src="https://cdn.yodlee.com/fastlink/v4/initialize.js"></script>

    <script>
        async function launchFastLink() {
            try {
                const res = await fetch("{{ url('/fastlink-token') }}");
                const data = await res.json();

                if (!data.accessToken) {
                    console.error("No access token returned:", data);
                    return;
                }

                console.log("Launching FastLink for user:", data.userLogin);

                window.fastlink.open({
                    fastLinkURL: data.fastLinkURL,
                    accessToken: `Bearer ${data.accessToken}`,
                    params: {
                        userExperienceFlow: 'Aggregation',
                        loginName: 'sbMem68ef21052da092' // sandbox user
                    },
                    onSuccess: d => console.log('✅ Success:', d),
                    onError: d => console.log('❌ Error:', d),
                    onExit: d => console.log('⚙️ Exit:', d),
                    onEvent: d => console.log('📡 Event:', d)
                }, 'container-fastlink');

            } catch (err) {
                console.error('Error launching FastLink:', err);
            }
        }

        document.getElementById('btn-fastlink').addEventListener('click', launchFastLink);
    </script>

</body>

</html>
