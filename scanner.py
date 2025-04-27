import requests
import sqlite3
import json
import time

RPC_URL = 'http://NODEIP:9332/'
RPC_USER = 'RPC_USER'
RPC_PASSWORD = 'RPC_PASS'

conn = sqlite3.connect('mweblist.db')
cursor = conn.cursor()

cursor.execute('''
    CREATE TABLE IF NOT EXISTS mweb_pegins (
        txid TEXT PRIMARY KEY,
        block_height INTEGER,
        amount REAL
    )
''')
cursor.execute('''
    CREATE TABLE IF NOT EXISTS scan_progress (
        id INTEGER PRIMARY KEY,
        last_scanned_block INTEGER
    )
''')
cursor.execute('''
    CREATE TABLE IF NOT EXISTS mweb_total (
        id INTEGER PRIMARY KEY,
        mweb_total REAL
    )
''')
conn.commit()

def rpc_request(method, params=None):
    payload = json.dumps({
        'jsonrpc': '1.0',
        'id': 'python',
        'method': method,
        'params': params or []
    })
    response = requests.post(
        RPC_URL,
        data=payload,
        headers={'Content-Type': 'text/plain'},
        auth=(RPC_USER, RPC_PASSWORD)
    )
    response.raise_for_status()
    return response.json()['result']

def get_last_scanned_block():
    cursor.execute("SELECT last_scanned_block FROM scan_progress WHERE id = 1")
    row = cursor.fetchone()
    return int(row[0]) if row else 2265950

def set_last_scanned_block(height):
    cursor.execute('''
        INSERT INTO scan_progress (id, last_scanned_block) VALUES (1, ?)
        ON CONFLICT(id) DO UPDATE SET last_scanned_block=excluded.last_scanned_block
    ''', (height,))
    conn.commit()

def get_mweb_total():
    cursor.execute("SELECT mweb_total FROM mweb_total WHERE id = 1")
    row = cursor.fetchone()
    return float(row[0]) if row else 0.0

def set_mweb_total(total):
    cursor.execute('''
        INSERT INTO mweb_total (id, mweb_total) VALUES (1, ?)
        ON CONFLICT(id) DO UPDATE SET mweb_total=excluded.mweb_total
    ''', (total,))
    conn.commit()

def scan_blocks():
    start = get_last_scanned_block()
    tip = rpc_request('getblockcount')

    for height in range(start, tip + 1):
        blockhash = rpc_request('getblockhash', [height])
        block = rpc_request('getblock', [blockhash, 2])

        mweb_total = 0.0
        peg_in_count = 0

        print(f"Scanning block {height}...")

        for tx in block['tx']:
            for vout in tx.get('vout', []):
                type_ = vout.get('scriptPubKey', {}).get('type', '')
                value = vout.get('value', 0.0)

                if type_ == 'witness_mweb_hogaddr':
                    mweb_total += value

                if type_ == 'witness_mweb_pegin':
                    cursor.execute('''
                        INSERT OR IGNORE INTO mweb_pegins (txid, block_height, amount)
                        VALUES (?, ?, ?)
                    ''', (tx['txid'], height, value))
                    peg_in_count += 1

        if mweb_total > 0:
            set_mweb_total(mweb_total)

        set_last_scanned_block(height)

        print(f"Scanned block {height} (MWEB total: {mweb_total}), Peg-ins detected: {peg_in_count}")

if __name__ == "__main__":
    try:
        scan_blocks()
    except KeyboardInterrupt:
        print("Scanning interrupted by user.")
    finally:
        conn.close()
