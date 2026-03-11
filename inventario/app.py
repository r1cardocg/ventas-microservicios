from flask import Flask
from flask_cors import CORS
from dotenv import load_dotenv
import firebase_admin
from firebase_admin import credentials, db as firebase_db
import os

load_dotenv()

app = Flask(__name__)
CORS(app)

cred = credentials.Certificate("serviceAccountKey.json")
firebase_admin.initialize_app(cred, {
    'databaseURL': os.getenv('DATABASE_URL')
})

from routes import bp
app.register_blueprint(bp)

if __name__ == '__main__':
    app.run(debug=True, port=5000)