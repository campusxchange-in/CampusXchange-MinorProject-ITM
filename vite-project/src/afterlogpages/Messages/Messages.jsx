import React, { useState, useEffect, useRef } from "react";
import { useParams, useNavigate } from "react-router-dom";
import Navbar from "../components/Navbar";
import {
  FaSearch,
  FaCommentDots,
  FaPaperclip,
  FaSmile,
  FaPaperPlane,
  FaEllipsisV,
  FaArrowLeft,
  FaMapMarkerAlt,
  FaShoppingBag,
  FaCheckDouble,
  FaCheck,
  FaRegClock,
  FaFlag,
} from "react-icons/fa";

import "./Messages.css";

import {
  fetchUserChats,
  fetchUserChatMessages,
  sendUserChatMessage,
} from "../../services/userChatService";
import UserModal from "../components/UserModal";
import ReportUserModal from "./ReportUserModal";
import { markUserChatSold } from "../../services/userMarketService";
import {
  userFormatPrice,
  userParseDate,
  userInitial,
} from "../../utils/userFormat";

// ================================
// LIVE CHAT (database)
// ================================
// Chats + messages come from backend/user/chats_list.php,
// chat_messages.php and message_send.php. The open chat is refreshed every
// few seconds. Sent messages cannot be edited or deleted (evidence rule).
// The route /messages/:sellerId now carries the chat id.

const AVATAR_COLORS = ["blue", "pink", "green", "orange", "purple"];

const INBOX_REFRESH_MS = 8000;
const CHAT_REFRESH_MS = 4000;

const timeLabel = (value) => {
  const d = userParseDate(value);
  if (!d) return "";
  const sameDay = d.toDateString() === new Date().toDateString();
  return sameDay
    ? d.toLocaleTimeString("en-IN", { hour: "numeric", minute: "2-digit", hour12: true })
    : d.toLocaleDateString("en-IN", { day: "numeric", month: "short" });
};

// "Last seen ..." from the number of seconds since the user was last active
// (calculated by the server, so it does not depend on this device's clock).
const lastSeenLabel = (secondsAgo) => {
  if (secondsAgo === null || secondsAgo === undefined) return "Offline";
  if (secondsAgo < 120) return "Last seen just now";

  const d = new Date(Date.now() - secondsAgo * 1000);
  const time = d.toLocaleTimeString("en-IN", { hour: "numeric", minute: "2-digit", hour12: true });
  const midnight = (x) => new Date(x.getFullYear(), x.getMonth(), x.getDate()).getTime();
  const days = Math.round((midnight(new Date()) - midnight(d)) / 86400000);

  if (days <= 0) return `Last seen today at ${time}`;
  if (days === 1) return `Last seen yesterday at ${time}`;
  return `Last seen ${d.toLocaleDateString("en-IN", { day: "numeric", month: "short" })}`;
};

// message life cycle: sending (SYN) -> sent (server ACK) -> delivered -> read
const STATUS_RANK = { sending: 0, sent: 1, delivered: 2, read: 3 };

const MessageTicks = ({ status }) => {
  if (status === "sending") return <FaRegClock className="cx-tick cx-tick-sending" title="Sending" />;
  if (status === "sent") return <FaCheck className="cx-tick cx-tick-sent" title="Sent" />;
  if (status === "delivered") return <FaCheckDouble className="cx-tick cx-tick-delivered" title="Delivered" />;
  return <FaCheckDouble className="cx-tick cx-tick-read" title="Seen" />;
};

const toConversation = (c) => ({
  id: c.id,
  name: c.name,
  initial: userInitial(c.name),
  color: AVATAR_COLORS[c.other_id % AVATAR_COLORS.length],
  online: c.online,
  lastSeenAgo: c.last_seen_ago,
  unread: c.unread,
  type: c.type,
  productId: c.product_id,
  productStatus: c.product_status,
  priceValue: c.price,
  product: c.product,
  description: c.description || "",
  price: userFormatPrice(c.price),
  lastText: c.last_text || "",
  lastTime: timeLabel(c.last_at),
  messages: [],
});

const toMessage = (m) => ({
  id: m.id,
  sender: m.sender,
  text: m.text,
  time: timeLabel(m.sent_at),
  status: m.sender === "me" ? m.status || "sent" : null,
});

// lowest id of my messages that are not "read" yet (0 = nothing left to track)
const pendingStatusFrom = (messages) => {
  const ids = messages
    .filter((m) => m.sender === "me" && m.status !== "read" && typeof m.id === "number")
    .map((m) => m.id);
  return ids.length ? Math.min(...ids) : 0;
};

const lastMessagePreview = (conv) =>
  conv.messages.length
    ? conv.messages[conv.messages.length - 1].text
    : conv.lastText;

const lastMessageTime = (conv) =>
  conv.messages.length
    ? conv.messages[conv.messages.length - 1].time
    : conv.lastTime;

const Messages = () => {
  const { sellerId: chatIdParam } = useParams();
  const navigate = useNavigate();

  const [conversations, setConversations] = useState([]);
  const [selectedChatId, setSelectedChatId] = useState(
    chatIdParam ? Number(chatIdParam) : null
  );
  const [message, setMessage] = useState("");
  const [mobileChat, setMobileChat] = useState(Boolean(chatIdParam));
  const [searchTerm, setSearchTerm] = useState("");
  const [activeFilter, setActiveFilter] = useState("All");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [sending, setSending] = useState(false);
  const [moreOpen, setMoreOpen] = useState(false);      // 3-dot menu
  const [reportOpen, setReportOpen] = useState(false);  // report user modal
  const [reportNotice, setReportNotice] = useState("");
  const moreRef = useRef(null);
  const [soldModal, setSoldModal] = useState(false);
  const [soldBusy, setSoldBusy] = useState(false);
  const [soldError, setSoldError] = useState("");

  const lastIdRef = useRef({}); // chatId -> last message id loaded
  const conversationsRef = useRef([]);
  conversationsRef.current = conversations;

  // ---- inbox (list of chats), refreshed regularly ----
  const loadInbox = async () => {
    try {
      const d = await fetchUserChats();
      setConversations((prev) =>
        d.chats.map((c) => {
          const fresh = toConversation(c);
          const old = prev.find((p) => p.id === c.id);
          // keep already-loaded messages for the open chat
          return old ? { ...fresh, messages: old.messages } : fresh;
        })
      );
      setError("");
    } catch (e) {
      setError(e.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadInbox();
    const timer = setInterval(loadInbox, INBOX_REFRESH_MS);
    return () => clearInterval(timer);
  }, []);

  // Route change (e.g. coming from Buy Product page)
  useEffect(() => {
    if (chatIdParam) {
      setSelectedChatId(Number(chatIdParam));
      setMobileChat(true);
    }
  }, [chatIdParam]);

  // Default selection for a plain "/messages" visit
  useEffect(() => {
    if (!selectedChatId && conversations.length) {
      setSelectedChatId(conversations[0].id);
    }
  }, [conversations, selectedChatId]);

  const currentChat =
    conversations.find((c) => c.id === selectedChatId) || conversations[0];

  const currentChatId = currentChat?.id;

  // close the 3-dot menu on outside click / Escape
  useEffect(() => {
    if (!moreOpen) return undefined;
    const onDown = (e) => {
      if (moreRef.current && !moreRef.current.contains(e.target)) setMoreOpen(false);
    };
    const onKey = (e) => e.key === "Escape" && setMoreOpen(false);
    document.addEventListener("mousedown", onDown);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onDown);
      document.removeEventListener("keydown", onKey);
    };
  }, [moreOpen]);

  // ---- only the message list scrolls; keep it pinned to the newest message ----
  const messagesBoxRef = useRef(null);
  const stickToBottomRef = useRef(true);

  const handleMessagesScroll = () => {
    const el = messagesBoxRef.current;
    if (!el) return;
    // "at the bottom" = within ~80px of it; if the user scrolled up to read
    // older messages, new ones do not yank the view down
    stickToBottomRef.current = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
  };

  // opening another chat always starts at the newest message
  useEffect(() => {
    stickToBottomRef.current = true;
  }, [currentChatId]);

  const messageCount = currentChat?.messages.length || 0;
  const lastMessageIsMine =
    currentChat?.messages[messageCount - 1]?.sender === "me";

  useEffect(() => {
    const el = messagesBoxRef.current;
    if (!el) return;
    if (stickToBottomRef.current || lastMessageIsMine) {
      el.scrollTop = el.scrollHeight;
    }
  }, [currentChatId, messageCount, lastMessageIsMine]);

  // ---- messages of the open chat (full load, then poll for new ones) ----
  useEffect(() => {
    if (!currentChatId) return;

    let active = true;

    const pull = async () => {
      const after = lastIdRef.current[currentChatId] || 0;

      // my messages that are not "read" yet -> ask the server for their new state
      const open = conversationsRef.current.find((c) => c.id === currentChatId);
      const statusFrom = open ? pendingStatusFrom(open.messages) : 0;

      // "read" is only reported while this tab is really on screen
      const visible = document.visibilityState === "visible";

      try {
        const d = await fetchUserChatMessages(currentChatId, after, statusFrom, visible);
        if (!active) return;

        lastIdRef.current[currentChatId] = Math.max(after, d.last_id);

        const statusById = new Map((d.statuses || []).map((x) => [x.id, x.status]));

        setConversations((prev) =>
          prev.map((c) => {
            if (c.id !== currentChatId) return c;

            const known = new Set(c.messages.map((m) => m.id));
            const added = d.messages
              .filter((m) => !known.has(m.id))
              .map(toMessage);

            // ticks only ever move forward (sent -> delivered -> read)
            const updated = c.messages.map((m) => {
              const next = statusById.get(m.id);
              return next && STATUS_RANK[next] > (STATUS_RANK[m.status] ?? -1)
                ? { ...m, status: next }
                : m;
            });

            return {
              ...c,
              unread: visible ? 0 : c.unread,
              messages: [...updated, ...added],
            };
          })
        );
      } catch (e) {
        if (active) setError(e.message);
      }
    };

    // coming back to the tab = the user can see the chat again -> report "read" now
    const onVisible = () => {
      if (document.visibilityState === "visible") pull();
    };
    document.addEventListener("visibilitychange", onVisible);

    pull();
    const timer = setInterval(pull, CHAT_REFRESH_MS);

    return () => {
      active = false;
      clearInterval(timer);
      document.removeEventListener("visibilitychange", onVisible);
    };
  }, [currentChatId]);

  const handleSend = async () => {
    const text = message.trim();
    if (!text || !currentChat || sending) return;

    setSending(true);

    // 1) SYN: show it at once with a clock while the request is on its way
    const chatId = currentChat.id;
    const tempId = `tmp-${Date.now()}`;
    const pending = {
      id: tempId,
      sender: "me",
      text,
      time: timeLabel(new Date().toISOString()),
      status: "sending",
    };

    setConversations((prev) =>
      prev.map((c) =>
        c.id === chatId ? { ...c, messages: [...c.messages, pending] } : c
      )
    );
    setMessage("");

    try {
      // 2) server ACK: the message is stored -> one tick
      const d = await sendUserChatMessage(chatId, text);
      const sent = toMessage(d.message);

      // NB: lastIdRef is NOT moved here. A message from the other person with a
      // smaller id may not have been fetched yet; the next poll brings it in
      // (duplicates are skipped by id).
      setConversations((prev) =>
        prev.map((c) => {
          if (c.id !== chatId) return c;
          const alreadyPolled = c.messages.some((m) => m.id === sent.id);
          return {
            ...c,
            messages: alreadyPolled
              ? c.messages.filter((m) => m.id !== tempId)
              : c.messages.map((m) => (m.id === tempId ? sent : m)),
          };
        })
      );
      setError("");
    } catch (e) {
      // not stored: take it back out and give the text back to the user
      setConversations((prev) =>
        prev.map((c) =>
          c.id === chatId
            ? { ...c, messages: c.messages.filter((m) => m.id !== tempId) }
            : c
        )
      );
      setMessage((current) => current || text);
      setError(e.message);
    } finally {
      setSending(false);
    }
  };

  // Seller picks this buyer: creates the order and marks the item Sold
  const handleMarkSold = async () => {
    if (!currentChat) return;

    setSoldBusy(true);
    setSoldError("");

    try {
      await markUserChatSold(currentChat.id, currentChat.priceValue);
      setSoldModal(false);
      await loadInbox();
      setError("");
    } catch (e) {
      setSoldError(e.message);
    } finally {
      setSoldBusy(false);
    }
  };

  const openChat = (id) => {
    setSelectedChatId(id);
    setMobileChat(true);
    setConversations((prev) =>
      prev.map((c) => (c.id === id ? { ...c, unread: 0 } : c))
    );
  };

  const filteredConversations = conversations.filter((c) => {
    const term = searchTerm.toLowerCase();

    const matchesSearch =
      c.name.toLowerCase().includes(term) ||
      c.product.toLowerCase().includes(term);

    const matchesFilter =
      activeFilter === "All" ||
      (activeFilter === "Unread" && c.unread > 0) ||
      (activeFilter === "Buying" && c.type === "buying") ||
      (activeFilter === "Selling" && c.type === "selling");

    return matchesSearch && matchesFilter;
  });

  if (!currentChat) {
    return (
      <div className="cx-messages-root">
        <Navbar />
        <div className="cx-messages-page">
          <main className="cx-message-main">
            <p>{loading ? "Loading conversations..." : error || "No conversations yet."}</p>
          </main>
        </div>
      </div>
    );
  }

  return (
    <div className="cx-messages-root">
      <Navbar />
      <div className="cx-messages-page">
        <main className="cx-message-main">
          <div className="cx-message-heading">
            <div>
              <h1>Messages</h1>
              <p>Connect with buyers and sellers on CampusXchange.</p>
            </div>

            <div className="cx-inbox-count">
              <FaCommentDots />
              {conversations.length} conversations
            </div>
          </div>

          <div className={`cx-chat-layout ${mobileChat ? "mobile-chat-open" : ""}`}>
            {/* ================= LEFT ================= */}
            <aside className="cx-conversations">
              <div className="cx-conversation-title">
                <div>
                  <h2>Inbox</h2>
                  <p>Your recent conversations</p>
                </div>
              </div>

              <div className="cx-inbox-search">
                <FaSearch />
                <input
                  type="text"
                  placeholder="Search messages..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                />
              </div>

              <div className="cx-filters">
                {["All", "Unread", "Buying", "Selling"].map((f) => (
                  <button
                    key={f}
                    className={activeFilter === f ? "active" : ""}
                    onClick={() => setActiveFilter(f)}
                  >
                    {f}
                  </button>
                ))}
              </div>

              <div className="cx-conversation-list">
                {filteredConversations.length === 0 && (
                  <p style={{ padding: "10px", fontSize: "11px", color: "#9ba0ae" }}>
                    No conversations found.
                  </p>
                )}

                {filteredConversations.map((chat) => (
                  <div
                    key={chat.id}
                    onClick={() => openChat(chat.id)}
                    className={`cx-conversation ${
                      currentChat.id === chat.id ? "selected" : ""
                    }`}
                  >
                    <div className={`cx-avatar ${chat.color}`}>
                      {chat.initial}
                      {chat.online && <span className="cx-online"></span>}
                    </div>

                    <div className="cx-conversation-info">
                      <div className="cx-conversation-name">
                        <h3>{chat.name}</h3>
                        <span>{lastMessageTime(chat)}</span>
                      </div>

                      <div className="cx-last-message">
                        <p>{lastMessagePreview(chat)}</p>
                        {chat.unread > 0 && (
                          <span className="cx-unread">{chat.unread}</span>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </aside>

            {/* ================= RIGHT CHAT ================= */}
            <section className="cx-active-chat">
              <div className="cx-chat-header">
                <div className="cx-chat-person">
                  <button className="cx-back-btn" onClick={() => setMobileChat(false)}>
                    <FaArrowLeft />
                  </button>

                  <div className={`cx-avatar ${currentChat.color}`}>
                    {currentChat.initial}
                    {currentChat.online && <span className="cx-online"></span>}
                  </div>

                  <div>
                    <h2>{currentChat.name}</h2>
                    <p>
                      <span className={currentChat.online ? "" : "offline"}></span>
                      {currentChat.online
                        ? "Active now"
                        : lastSeenLabel(currentChat.lastSeenAgo)}
                    </p>
                  </div>
                </div>

                <div className="cx-more-wrap" ref={moreRef}>
                  <button
                    type="button"
                    className="cx-more-btn"
                    aria-haspopup="menu"
                    aria-expanded={moreOpen}
                    onClick={() => setMoreOpen((v) => !v)}
                  >
                    <FaEllipsisV />
                  </button>

                  {moreOpen && (
                    <div className="cx-more-menu" role="menu">
                      <button
                        type="button"
                        role="menuitem"
                        className="danger"
                        onClick={() => {
                          setMoreOpen(false);
                          setReportOpen(true);
                        }}
                      >
                        <FaFlag /> Report {currentChat.name}
                      </button>
                    </div>
                  )}
                </div>
              </div>

              <div className="cx-product-card">
                <div className="cx-product-icon">
                  <FaShoppingBag />
                </div>

                <div className="cx-product-info">
                  <small>ABOUT THIS ITEM</small>
                  <h3>{currentChat.product}</h3>
                  <p>{currentChat.description}</p>
                  <span>
                    <FaMapMarkerAlt />
                    CampusXchange
                  </span>
                </div>

                <div className="cx-product-price">
                  <strong>{currentChat.price}</strong>
                  <button onClick={() => navigate(`/buy/${currentChat.productId}`)}>
                    View Item
                  </button>
                  {currentChat.type === "selling" &&
                    currentChat.productStatus === "Active" && (
                      <button onClick={() => { setSoldError(""); setSoldModal(true); }}>Mark as Sold</button>
                    )}
                  {currentChat.productStatus === "Sold" && <span>Sold</span>}
                </div>
              </div>

              {error && (
                <p style={{ color: "#dc2626", padding: "6px 16px", margin: 0 }}>{error}</p>
              )}

              <div
                className="cx-chat-messages"
                ref={messagesBoxRef}
                onScroll={handleMessagesScroll}
              >
                <div className="cx-today">
                  <span>Today</span>
                </div>

                {currentChat.messages.length === 0 && (
                  <p style={{ textAlign: "center", fontSize: "11px", color: "#a0a5b2" }}>
                    Say hi to start the conversation.
                  </p>
                )}

                {currentChat.messages.map((chat) => (
                  <div
                    key={chat.id}
                    className={`cx-message ${chat.sender === "me" ? "sent" : "received"}`}
                  >
                    {chat.sender === "them" && (
                      <div className={`cx-avatar small ${currentChat.color}`}>
                        {currentChat.initial}
                      </div>
                    )}

                    <div>
                      <div className="cx-message-bubble">{chat.text}</div>
                      <div className="cx-message-time">
                        {chat.time}
                        {chat.sender === "me" && <MessageTicks status={chat.status} />}
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="cx-message-composer">
                {/* <button className="cx-attach">
                  <FaPaperclip />
                </button> */}

                <div className="cx-input-box">
                  <input
                    type="text"
                    value={message}
                    placeholder="Write a message..."
                    onChange={(e) => setMessage(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === "Enter") handleSend();
                    }}
                  />
                  <button type="button">
                    <FaSmile />
                  </button>
                </div>

                <button className="cx-send" onClick={handleSend}>
                  <FaPaperPlane />
                </button>
              </div>
            </section>
          </div>
        </main>
      </div>
    

      <ReportUserModal
        open={reportOpen}
        chatId={currentChat?.id}
        userName={currentChat?.name}
        onClose={() => setReportOpen(false)}
        onDone={(msg) => {
          setReportOpen(false);
          setReportNotice(msg);
        }}
      />

      <UserModal
        open={Boolean(reportNotice)}
        title="Report sent"
        onClose={() => setReportNotice("")}
      >
        {reportNotice}
      </UserModal>

      <UserModal
        open={soldModal}
        title="Mark as sold?"
        confirmText="Yes, mark as sold"
        onConfirm={handleMarkSold}
        onClose={() => setSoldModal(false)}
        loading={soldBusy}
        error={soldError}
      >
        Sell "{currentChat?.product}" to {currentChat?.name} for {currentChat?.price}? The
        listing will be marked Sold, an order will be created for the buyer and the other
        interested buyers will be notified. This cannot be undone.
      </UserModal>
    </div>
  );
};

export default Messages;
