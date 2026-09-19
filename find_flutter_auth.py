import os
import glob

search_dir = r"C:\flutter\eltarekapp\lib"
for root, dirs, files in os.walk(search_dir):
    for file in files:
        if file.endswith(".dart"):
            path = os.path.join(root, file)
            try:
                with open(path, "r", encoding="utf-8") as f:
                    content = f.read()
                    if "GoogleSignIn" in content or "googleLogin" in content or "BackendGateway" in content:
                        print(f"Match found in: {path}")
            except Exception as e:
                pass
