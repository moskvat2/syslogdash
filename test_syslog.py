import socket

def send_test_log(message, host='127.0.0.1', port=514):
    sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    # Formato simulando MikroTik
    full_msg = f"<190>May 1 13:30:00 MikroTik-Test firewall,info ICMP_LOG input: {message}"
    sock.sendto(full_msg.encode('utf-8'), (host, port))
    print(f"Sent: {full_msg}")

if __name__ == "__main__":
    send_test_log("Test message from Antigravity")
